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


#   f l o w
after "deploy:update_code", "deploy:cleanup"

namespace :deploy do
    task :update do
    	transaction do
    		update_code
        	set_cache_dirs
        	set_log_dir
          set_blackhole_path_symlink_fix
          spawn
    		symlink
        update_version
    	end
    end

    task :finalize_update do
    	transaction do
    		run "chmod -R g+w #{releases_path}/#{release_name}"
    	end
    end

    task :update_version do
    	transaction do
        	run "php #{current_release}/garp/scripts/garp.php Version update --e=#{garp_env}"
        	run "php #{current_release}/garp/scripts/garp.php Version update garp --e=#{garp_env}"
    	end
    end

    task :symlink do
    	transaction do
    		run "ln -nfs #{current_release} #{deploy_to}/#{current_dir}"
    	end
    end
    
    task :set_cache_dirs do
      transaction do
        # backend cache
        run "if [ ! -d '#{server_cache_dir}' ]; then mkdir -p #{server_cache_dir}; fi";
        run "if [ ! -d '#{server_cache_dir}/URI' ]; then mkdir -p #{server_cache_dir}/URI; fi";
        run "if [ ! -d '#{server_cache_dir}/HTML' ]; then mkdir -p #{server_cache_dir}/HTML; fi";
        run "if [ ! -d '#{server_cache_dir}/CSS' ]; then mkdir -p #{server_cache_dir}/CSS; fi";
        run "if [ ! -d '#{server_cache_dir}/tags' ]; then mkdir -p #{server_cache_dir}/tags; fi";
        # run "echo '<?php' > #{server_cache_dir}/pluginLoaderCache.php"
        
        # static html cache
        run "if [ ! -d '#{current_release}/public/cached' ]; then mkdir -p #{current_release}/public/cached; fi";
      end
    end

    task :set_log_dir do
      transaction do
        run "if [ ! -d '#{current_release}/application/data/logs' ]; then mkdir -p #{current_release}/application/data/logs; fi";
      end
    end
    
    task :set_blackhole_path_symlink_fix do
      transaction do
        # fix casing
    		run "ln -nfs BlackHole.php #{current_release}/library/Zend/Cache/Backend/Blackhole.php"
      end
    end

    task :spawn do
      transaction do
        run "php #{current_release}/garp/scripts/garp.php Spawn --e=#{garp_env}"
      end
    end

    task :migrate do
    	# nothing
    end

    task :restart do
    	# nothing
    end
end



#   p r o d u c t i o n   w a r n i n g
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
