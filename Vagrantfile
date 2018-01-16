# -*- mode: ruby -*-

require_relative 'vagrant/libraries/os_checker.rb'
require_relative 'vagrant/libraries/host_shell_commands.rb'
require_relative 'vagrant/libraries/vagrant_plugin_handler.rb'
require 'yaml'

Vagrant.require_version '>= 1.8.1'

required_plugins = %w(vagrant-host-shell);
if OS.windows?
  required_plugins << 'vagrant-winnfsd'
end


plugins_to_install = required_plugins.select { |plugin| not Vagrant.has_plugin? plugin }
VagrantPluginHandler.update plugins_to_install


Vagrant.configure("2") do |config|
  #Basic config
  config.vm.box = "ubuntu/xenial64"
  config.vm.network "private_network", ip: "192.168.33.11"
  config.vm.provider "virtualbox" do |vb|
    vb.memory = "8192"
    vb.customize [ "guestproperty", "set", :id, "/VirtualBox/GuestAdd/VBoxService/--timesync-set-threshold", 1000 ]
  end

  if OS.windows?
    config.vm.network "private_network", type: "dhcp"
  end

  if OS.windows? || OS.linux?
    config.vm.synced_folder "./", "/var/www", create: true, type: "nfs"
  else
    config.vm.synced_folder "./", "/var/www", create: true, owner: "vagrant", group: "www-data", mount_options: ["dmode=775,fmode=664"]
  end

  config.vm.provision "shell", path: "vagrant/scripts/php_setup.sh"
end
