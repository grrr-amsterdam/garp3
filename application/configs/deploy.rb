#set :log_level, :info
set :linked_dirs, %w{
	public/uploads/documents
	public/uploads/images
	application/data/logs
	application/data/cache/tags
}
set :keep_releases, 3

# Grab last release directory, if any
releases = capture("ls #{File.join(fetch(:deploy_to), 'releases')}")
set :this_host_last_release, releases.split("\n").sort.last

load "application/configs/deploy.rb"

Dir.glob("garp/deploy/tasks/*.cap").each { |r| load r }
load "garp/deploy/garp3.cap"
