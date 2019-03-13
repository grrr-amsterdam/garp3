set :linked_dirs, %w{
  public/uploads/documents
  public/uploads/images
  application/data/logs
  application/data/cache/tags
  application/data/cache/htmlpurifier
  public/cached
}

set :linked_files, %w{
  .env
}

set :keep_releases, 2

load "application/configs/deploy.rb"

Dir.glob("vendor/grrr-amsterdam/garp3/deploy/tasks/*.cap").each { |r| load r }
load "vendor/grrr-amsterdam/garp3/deploy/garp3.cap"

set :tmp_dir, "/tmp/#{fetch(:application)}-#{fetch(:stage)}"
set :interactive, ENV['interactive'] == nil || ENV['interactive'] == 'true'
