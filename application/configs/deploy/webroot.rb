module Webroot

  # Find webroot dir
  def self.find_webroot cap, deploy_to
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
        find_servers_for_task(current_task).each do |current_server|
          set :domain_dir, "#{pwd}/html/#{current_server.host}"
          if capture("[ -d #{domain_dir} ] && echo '1' || echo '0'").strip == '1' and capture("[ -d #{domain_dir}/public ] && echo '1' || echo '0'").strip == '1'
            set :deploy_to, "#{domain_dir}/public"
            set :unset_deploy_to, deploy_to
          else
            raise "Can't autodetect the webroot dir, I know it's not: #{domain_dir}/public"
          end
        end
      elsif capture("[ -d #{pwd}/httpdocs ] && echo '1' || echo '0'").strip == '1'
        set :deploy_to, "#{pwd}/httpdocs"
        set :unset_deploy_to, deploy_to
      else
        raise "Oops! :deploy_to is not set, and I can't seem to find the webroot directory myself..."
      end
    end
  end

end
