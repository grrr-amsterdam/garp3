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
set :use_sudo, false
set :keep_releases, 2

set (:document_root) {"#{deploy_to}/current/public"}
set (:server_cache_dir) {"#{current_release}/application/data/cache"}
set :ssh_keys, File.read("garp/application/configs/authorized_keys")

basepath = "garp/application/configs/deploy/"
load "#{basepath}disk.rb"
load "#{basepath}webroot.rb"
load "#{basepath}crontab.rb"
load "#{basepath}auth.rb"
load "#{basepath}garp.rb"



#   d e p l o y
after "deploy:update_code", "deploy:cleanup"

namespace :deploy do
  desc "Set up server instance"
  task :setup do
      Auth.add_public_ssh_keys self, ssh_keys
      Webroot.find_webroot self, deploy_to, current_task
      Auth.mark_git_server_safe self

	  Disk.create_deploy_dirs self, deploy_to
      Auth.set_shared_dirs_permissions self, deploy_to
      create_webroot_reroute_htaccess
      Crontab.install_crontab self, deploy_to, garp_env
      prompt_to_set_newly_found_deploy_dir
  end

  desc "Deploy project"
  task :update do
    transaction do
	  before_deploy
      update_code
      Disk.create_system_cache_dirs self, server_cache_dir
      Disk.create_static_cache_dir self, current_release
      Disk.create_log_dir self, current_release
      Disk.set_blackhole_path_symlink_fix self, current_release
      Garp.spawn self, current_release, garp_env
      Garp.update_version self, current_release, garp_env
      Garp.env_setup self, current_release, garp_env
      Auth.set_webroot_permissions self, releases_path, release_name
      symlink
	  after_deploy
    end
  end

  # Overwritten because cap looks for Rails directories (javascripts, stylesheets, images)
  desc "Finalize update"
  task :finalize_update do
	  transaction do
	  	  # zzzz
	  end
  end

  task :before_deploy do
	begin
		AppHooks.before_deploy
	rescue NameError
    end
  end

  task :after_deploy do
	begin
		AppHooks.after_deploy
	rescue NameError
    end
  end


  # ------- P R I V A T E    M E T H O D S
  
  desc "Create .htaccess file to reroute webroot"
  task :create_webroot_reroute_htaccess do
    run "printf '<IfModule mod_rewrite.c>\\n\\tRewriteEngine on\\n\\tRewriteRule ^(.*)$ current/public/$1 [L]\\n</IfModule>' > #{deploy_to}/.htaccess"
  end
  
  task :prompt_to_set_newly_found_deploy_dir do
    if exists?(:unset_deploy_to)
      puts("\033[1;31mDone. Now please set :deploy_to in deploy.rb to:\n#{unset_deploy_to}\033[0m")
    end
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
