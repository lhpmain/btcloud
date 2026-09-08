<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;
use think\facade\Config;
use app\lib\Plugins;

class Clean extends Command
{
    protected function configure()
    {
        $this->setName('clean')
            ->setDescription('the clean command');
    }

    protected function execute(Input $input, Output $output)
    {
        $res = Db::name('config')->cache('configs',0)->column('value','key');
        Config::set($res, 'sys');
        
        if(config_get('bt_url') || config_get('bt_surl')){
            $this->clean_plugins($input, $output, 'Linux');
        }
        if(config_get('wbt_url') || config_get('wbt_surl')){
            $this->clean_plugins($input, $output, 'Windows');
        }
        if(config_get('enbt_url') || config_get('enbt_surl')){
            $this->clean_plugins($input, $output, 'en');
        }

        config_set('cleantime', date('Y-m-d H:i:s'));
    }

    private function clean_plugins(Input $input, Output $output, $os){
        $data_dir = get_data_dir($os) . 'plugins/';
        $file_list = [];
        $json_arr = Plugins::get_plugin_list($os);
        if(!$json_arr || empty($json_arr['list']) || !is_array($json_arr['list'])) return;
        foreach($json_arr['list'] as $plugin){
            foreach($plugin['versions'] as $version){
                $ver = $version['m_version'].'.'.$version['version'];
                if(!isset($version['download'])){
                    $file_list[] = $plugin['name'].'-'.$ver;
                }
            }
        }

        $count = 0;
        $package_dir = $data_dir.'package';
        if(is_dir($package_dir)){
            $dir = opendir($package_dir);
            if($dir){
                while(false !== ( $file = readdir($dir)) ) {
                    if($file == '.' || $file == '..') continue;
                    $name = str_replace('.zip', '', $file);
                    if(!in_array($name, $file_list)){
                        $filepath = $data_dir . 'package/' . $file;
                        unlink($filepath);
                        $count++;
                    }
                }
                closedir($dir);
            }
        }
        $output->writeln($os.'成功清理'.$count.'个历史版本插件包');

        $count = 0;
        $folder_dir = $data_dir.'folder';
        if(is_dir($folder_dir)){
            $dir = opendir($folder_dir);
            if($dir){
                while(false !== ( $file = readdir($dir)) ) {
                    if($file == '.' || $file == '..') continue;
                    if(!in_array($file, $file_list)){
                        $filepath = $data_dir . 'folder/' . $file;
                        deleteDir($filepath);
                        $count++;
                    }
                }
                closedir($dir);
            }
        }
        $output->writeln($os.'成功清理'.$count.'个历史版本插件目录');
    }
}
