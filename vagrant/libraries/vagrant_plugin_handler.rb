class VagrantPluginHandler
	def self.update(plugins_to_install)
		if not plugins_to_install.empty?
		  puts "Installing plugins: #{plugins_to_install.join(' ')}"
		  if system "vagrant plugin install #{plugins_to_install.join(' ')}"
		    exec "vagrant #{ARGV.join(' ')}"
		  else
		    abort "Installation of one or more plugins has failed. Aborting."
		  end
		end
	end
end
