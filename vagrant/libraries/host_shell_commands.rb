class HostShellCommands
	def self.run_shell_cmd(cmd)
		feedback = %x(#{cmd})
		success = ($?.exitstatus == 0)
		unless success
			puts "#{cmd}"
			puts "     #{feedback}"
		else
			puts "#{cmd}"
			puts "     #{feedback}"
		end
	end

end
