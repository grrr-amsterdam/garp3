module Webroot

  # Find webroot dir
  def self.find_webroot cap, deploy_to, current_task
    if deploy_to.start_with?('/u/apps/')
      # deploy_to is not set yet
      pwd = cap.capture("pwd").strip

      if cap.capture("[ -d #{pwd}/web ] && echo '1' || echo '0'").strip == '1'
        cap.set :deploy_to, "#{pwd}/web"
        cap.set :unset_deploy_to, deploy_to
      elsif cap.capture("[ -d #{pwd}/public ] && echo '1' || echo '0'").strip == '1'
        cap.set :deploy_to, "#{pwd}/public"
        cap.set :unset_deploy_to, deploy_to
      elsif cap.capture("[ -d #{pwd}/html ] && echo '1' || echo '0'").strip == '1'
        find_servers_for_task(current_task).each do |current_server|
          cap.set :domain_dir, "#{pwd}/html/#{current_server.host}"
          if cap.capture("[ -d #{domain_dir} ] && echo '1' || echo '0'").strip == '1' and cap.capture("[ -d #{domain_dir}/public ] && echo '1' || echo '0'").strip == '1'
            cap.set :deploy_to, "#{domain_dir}/public"
            cap.set :unset_deploy_to, deploy_to
          else
            raise "Can't autodetect the webroot dir, I know it's not: #{domain_dir}/public"
          end
        end
      elsif cap.capture("[ -d #{pwd}/httpdocs ] && echo '1' || echo '0'").strip == '1'
        cap.set :deploy_to, "#{pwd}/httpdocs"
        cap.set :unset_deploy_to, deploy_to
      else
        raise "Oops! :deploy_to is not set, and I can't seem to find the webroot directory myself..."
      end
    end
  end

end
