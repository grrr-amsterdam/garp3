module Garp

  # Spawn models
  def self.spawn cap, current_release, garp_env
    cap.run "php #{current_release}/garp/scripts/garp.php Spawn --e=#{garp_env}"
  end
    
  # Update the application and Garp version numbers
  def self.update_version cap, current_release, garp_env
  	cap.run "php #{current_release}/garp/scripts/garp.php Version update --e=#{garp_env}"
  	cap.run "php #{current_release}/garp/scripts/garp.php Version update garp --e=#{garp_env}"
  end

  # Perform administrative tasks after deploy
  def self.env_setup cap, current_release, garp_env
  	cap.run "php #{current_release}/garp/scripts/garp.php Env setup --e=#{garp_env}"
  end

end
