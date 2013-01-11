#   s t a g e s
set :stages, %w(integration staging production)
require 'capistrano/ext/multistage'
set :stage, nil
set :default_stage, "integration"


#   v e r s i o n   c o n t r o l
set :scm, :git


#   r e m o t e   s e r v e r
set :deploy_via, :remote_cache
ssh_options[:forward_agent] = true
set :git_enable_submodules, 1
set :use_sudo, false
set :keep_releases, 3

set (:document_root) {"#{deploy_to}/current/public"}
set (:server_cache_dir) {"#{current_release}/application/data/cache"}
set :ssh_keys, File.read("garp/application/configs/authorized_keys")



#   d e p l o y
after "deploy:update_code", "deploy:cleanup"

namespace :deploy do
  desc "Set up server instance"
  task :setup do
    transaction do
      add_public_ssh_keys
      find_webroot
      mark_git_server_safe
      create_deploy_dirs
      set_shared_dirs_permissions
      create_webroot_reroute_htaccess
      prompt_to_set_newly_found_deploy_dir
    end
  end

  desc "Deploy project"
  task :update do
    transaction do
      update_code
      create_system_cache_dirs
      create_static_cache_dir
      create_log_dir
      set_blackhole_path_symlink_fix
      spawn
      update_version
      set_webroot_permissions
      symlink
    end
  end


  # ------- P R I V A T E   S E T U P   M E T H O D S
  
  desc "Add public SSH keys"
  task :add_public_ssh_keys do
    run "if [ ! -d '~/.ssh' ]; then mkdir -p ~/.ssh; fi"
    run "chmod 700 ~/.ssh"
    run "echo -e \'#{ssh_keys}\' > ~/.ssh/authorized_keys"
    run "chmod 700 ~/.ssh/authorized_keys"
  end

  desc "Find webroot dir"
  task :find_webroot do
    if deploy_to.start_with?('/u/apps/')
      # deploy_to is not set yet
      set :pwd, capture("pwd").strip

      if capture("[ -d #{pwd}/web ] && echo '1' || echo '0'").strip == '1'
        set :deploy_to, "#{pwd}/web"
        set :unset_deploy_to, deploy_to
      elsif capture("[ -d #{pwd}/public ] && echo '1' || echo '0'").strip == '1'
        set :deploy_to, "#{pwd}/public"
        set :unset_deploy_to, deploy_to
      elsif capture("[ -d #{pwd}/html ] && echo '1' || echo '0'").strip == '1'
        set :deploy_to, "#{pwd}/html"
        set :unset_deploy_to, deploy_to
      elsif capture("[ -d #{pwd}/httpdocs ] && echo '1' || echo '0'").strip == '1'
        set :deploy_to, "#{pwd}/httpdocs"
        set :unset_deploy_to, deploy_to
      else
        raise "Oops! :deploy_to is not set, and I can't seem to find the webroot directory myself..."
      end
    end
  end

  desc "Mark Git server as safe"
  task :mark_git_server_safe do
    run "touch ~/.ssh/known_hosts && ssh-keyscan -t rsa,dsa flow.grrr.nl 2>&1 | sort -u - ~/.ssh/known_hosts > ~/.ssh/tmp_hosts && cat ~/.ssh/tmp_hosts > ~/.ssh/known_hosts && rm ~/.ssh/tmp_hosts"
  end

  desc "Create essential deploy directories"
  task :create_deploy_dirs do
    run "if [ ! -d '#{deploy_to}/releases' ]; then mkdir -p #{deploy_to}/releases; fi"
    run "if [ ! -d '#{deploy_to}/shared/uploads/documents' ]; then mkdir -p #{deploy_to}/shared/uploads/documents; fi"
    run "if [ ! -d '#{deploy_to}/shared/uploads/images' ]; then mkdir -p #{deploy_to}/shared/uploads/images; fi"
  end
  
  desc "Set permissions on essential deploy directories"
  task :set_shared_dirs_permissions do
      run "chmod -R g+w #{deploy_to}/shared/uploads/documents"
      run "chmod -R g+w #{deploy_to}/shared/uploads/images"
  end
  
  desc "Create .htaccess file to reroute webroot"
  task :create_webroot_reroute_htaccess do
    run "echo -e '<IfModule mod_rewrite.c>\\n\\tRewriteEngine on\\n\\tRewriteRule ^(.*)$ current/public/$1 [L]\\n</IfModule>' > #{deploy_to}/.htaccess"
  end
  
  task :prompt_to_set_newly_found_deploy_dir do
    if exists?(:unset_deploy_to)
      puts("\033[1;31mDone. Now please set :deploy_to in deploy.rb to #{unset_deploy_to}\033[0m")
    end
  end



  # ------- P R I V A T E   D E P L O Y   M E T H O D S
  
  desc "Create backend cache directories"
  task :create_system_cache_dirs do
    run "if [ ! -d '#{server_cache_dir}' ]; then mkdir -p #{server_cache_dir}; fi";
    run "if [ ! -d '#{server_cache_dir}/URI' ]; then mkdir -p #{server_cache_dir}/URI; fi";
    run "if [ ! -d '#{server_cache_dir}/HTML' ]; then mkdir -p #{server_cache_dir}/HTML; fi";
    run "if [ ! -d '#{server_cache_dir}/CSS' ]; then mkdir -p #{server_cache_dir}/CSS; fi";
    run "if [ ! -d '#{server_cache_dir}/tags' ]; then mkdir -p #{server_cache_dir}/tags; fi";
    # run "echo '<?php' > #{server_cache_dir}/pluginLoaderCache.php"
  end
  
  desc "Create static html cache directory"
  task :create_static_cache_dir do
    run "if [ ! -d '#{current_release}/public/cached' ]; then mkdir -p #{current_release}/public/cached; fi";
  end

  desc "Make sure the log file directory is present"
  task :create_log_dir do
    run "if [ ! -d '#{current_release}/application/data/logs' ]; then mkdir -p #{current_release}/application/data/logs; fi";
  end

  desc "Fix casing"
  task :set_blackhole_path_symlink_fix do
		run "ln -nfs BlackHole.php #{current_release}/library/Zend/Cache/Backend/Blackhole.php"
  end

  desc "Spawn models"
  task :spawn do
    run "php #{current_release}/garp/scripts/garp.php Spawn --e=#{garp_env}"
  end
    
  desc "Update the application and Garp version numbers"
  task :update_version do
  	run "php #{current_release}/garp/scripts/garp.php Version update --e=#{garp_env}"
  	run "php #{current_release}/garp/scripts/garp.php Version update garp --e=#{garp_env}"
  end

  desc "Set webroot directory permissions"
  task :set_webroot_permissions do
		run "chmod -R g+w #{releases_path}/#{release_name}"
  end
  
  desc "Point the webroot symlink to the current release"
  task :symlink do
		run "ln -nfs #{current_release} #{deploy_to}/#{current_dir}"
  end
end



desc "Throw a warning when deploying to production"
task :ask_production_confirmation do
  set(:confirmed) do
    puts <<-WARN

    ========================================================================

      WARNING: You're about to deploy to a live, public server.
      Please confirm that your work is ready for that.

    ========================================================================

    WARN
    answer = Capistrano::CLI.ui.ask "  Are you sure? (y) "
    if answer == 'y' then true else false end
  end

  unless fetch(:confirmed)
    puts "\nDeploy cancelled!"
    exit
  end
end

before 'production', :ask_production_confirmation
