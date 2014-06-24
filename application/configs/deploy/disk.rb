module Disk

  # Create essential deploy directories
  def self.create_deploy_dirs cap, deploy_to
    cap.run "if [ ! -d '#{deploy_to}/releases' ]; then mkdir -p #{deploy_to}/releases; fi"
    cap.run "if [ ! -d '#{deploy_to}/shared/backup/db' ]; then mkdir -p #{deploy_to}/shared/backup/db; fi"
    cap.run "if [ ! -d '#{deploy_to}/shared/uploads/documents' ]; then mkdir -p #{deploy_to}/shared/uploads/documents; fi"
    cap.run "if [ ! -d '#{deploy_to}/shared/uploads/images' ]; then mkdir -p #{deploy_to}/shared/uploads/images; fi"
    cap.run "if [ ! -d '#{deploy_to}/shared/logs' ]; then mkdir -p #{deploy_to}/shared/logs; fi"
    cap.run "if [ ! -d '#{deploy_to}/shared/tags' ]; then mkdir -p #{deploy_to}/shared/tags; fi";
  end

  # Fix casing
  def self.set_blackhole_path_symlink_fix cap, current_release
		cap.run "ln -nfs BlackHole.php #{current_release}/library/Zend/Cache/Backend/Blackhole.php"
  end

  # Create backend cache directories
  def self.create_system_cache_dirs cap, server_cache_dir
    cap.run "if [ ! -d '#{server_cache_dir}' ]; then mkdir -p #{server_cache_dir}; fi";
    cap.run "if [ ! -d '#{server_cache_dir}/URI' ]; then mkdir -p #{server_cache_dir}/URI; fi";
    cap.run "if [ ! -d '#{server_cache_dir}/HTML' ]; then mkdir -p #{server_cache_dir}/HTML; fi";
    cap.run "if [ ! -d '#{server_cache_dir}/CSS' ]; then mkdir -p #{server_cache_dir}/CSS; fi";
    cap.run "if [ ! -d '#{server_cache_dir}/tags' ]; then mkdir -p #{server_cache_dir}/tags; fi";
    # run "echo '<?php' > #{server_cache_dir}/pluginLoaderCache.php"
  end
  
  # Create static html cache directory
  def self.create_static_cache_dir cap, current_release
    cap.run "if [ ! -d '#{current_release}/public/cached' ]; then mkdir -p #{current_release}/public/cached; fi";
  end

  # Make sure the log file directory is present
  def self.create_log_dir cap, current_release
    cap.run "if [ ! -d '#{current_release}/application/data/logs' ]; then mkdir -p #{current_release}/application/data/logs; fi";
  end
end
