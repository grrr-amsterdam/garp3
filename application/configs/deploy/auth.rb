module Auth

  # Add public SSH keys
  def self.add_public_ssh_keys cap, ssh_keys
    cap.run "if [ ! -d '~/.ssh' ]; then mkdir -p ~/.ssh; fi"
    cap.run "chmod 700 ~/.ssh"
    cap.run "printf \'#{ssh_keys}\' > ~/.ssh/authorized_keys"
    cap.run "chmod 700 ~/.ssh/authorized_keys"
  end

  # Mark Git server as safe
  def self.mark_git_server_safe cap
    cap.run "touch ~/.ssh/known_hosts && ssh-keyscan -t rsa,dsa flow.grrr.nl 2>&1 | sort -u - ~/.ssh/known_hosts > ~/.ssh/tmp_hosts && cat ~/.ssh/tmp_hosts > ~/.ssh/known_hosts && rm ~/.ssh/tmp_hosts"
    cap.run "touch ~/.ssh/known_hosts && ssh-keyscan -t rsa,dsa code.grrr.nl 2>&1 | sort -u - ~/.ssh/known_hosts > ~/.ssh/tmp_hosts && cat ~/.ssh/tmp_hosts > ~/.ssh/known_hosts && rm ~/.ssh/tmp_hosts"
  end

  # Set permissions on essential deploy directories
  def self.set_shared_dirs_permissions cap, deploy_to
      cap.run "chmod -R g+w #{deploy_to}/shared/backup/db"
      cap.run "chmod -R g+w,o+rx #{deploy_to}/shared/uploads/documents"
      cap.run "chmod -R g+w,o+rx #{deploy_to}/shared/uploads/images"
      cap.run "chmod -R g+w,o+rx #{deploy_to}/shared/logs"
      cap.run "chmod -R g+w,o+rx #{deploy_to}/shared/tags"
  end

  # Set webroot directory permissions
  def self.set_webroot_permissions cap, releases_path, release_name
		cap.run "chmod -R g+w #{releases_path}/#{release_name}"
  end
end
