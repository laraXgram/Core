

function _sf_{{ COMMAND_NAME }}
    set sf_cmd (commandline -o)
    set c (count (commandline -oc))

    set completecmd "$sf_cmd[1]" "_complete" "--no-interaction" "-sfish" "-a{{ VERSION }}"

    for i in $sf_cmd
        if [ $i != "" ]
            set completecmd $completecmd "-i$i"
        end
    end

    set completecmd $completecmd "-c$c"

    $completecmd
end

complete -c '{{ COMMAND_NAME }}' -a '(_sf_{{ COMMAND_NAME }})' -f
