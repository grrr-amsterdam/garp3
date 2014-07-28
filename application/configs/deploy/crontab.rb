module Crontab
  # Install crontab
  def self.install_crontab cap, deploy_to, garp_env
  	php_exec 		= "/usr/bin/php"
  	garp_exec 		= "#{deploy_to}/current/garp/scripts/garp.php"
  	tab_frequent 	= "*/5 * * * * #{php_exec} #{garp_exec} cron frequently --e=#{garp_env} >/dev/null 2>&1"
  	tab_hourly 		= "0 * * * * #{php_exec} #{garp_exec} cron hourly --e=#{garp_env} >/dev/null 2>&1"
  	tab_daily 		= "0 4 * * * #{php_exec} #{garp_exec} cron daily --e=#{garp_env} >/dev/null 2>&1"
  	
  	cron_tmp_file 			= "/tmp/.crontab-tmp-output"
  	cmd_output_cron 		= "crontab -l > #{cron_tmp_file}"
	cmd_append	 			= 'if [ ! "`cat %s | grep \'%s\'`" ]; then echo "%s" | tee -a %s; fi;'
	cmd_install				= "crontab #{cron_tmp_file}"
	cmd_remove_cron_output 	= "rm #{cron_tmp_file}"

	cmd_frequent 	= sprintf cmd_append, cron_tmp_file, "cron frequently --e=#{garp_env}", tab_frequent, cron_tmp_file
	cmd_hourly 		= sprintf cmd_append, cron_tmp_file, "cron hourly --e=#{garp_env}", tab_hourly, cron_tmp_file
	cmd_daily 		= sprintf cmd_append, cron_tmp_file, "cron daily --e=#{garp_env}", tab_daily, cron_tmp_file

	begin 
		cap.run cmd_output_cron
	rescue Exception => error
		puts "No cronjob present yet"
	end

	# run cmd_output_cron
	cap.run cmd_frequent
	cap.run cmd_hourly
	cap.run cmd_daily
	cap.run cmd_install
	cap.run cmd_remove_cron_output
  end
end
