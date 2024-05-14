#!/bin/bash

kcptun_count=$(ps -ef | grep kcptun | grep -vc grep)
if [[ $kcptun_count -eq 0 ]]; then
    nohup /root/server_linux_amd64 -c kcptun.json > /dev/null 2>&1 &
fi

net_speeder=$(ps -ef | grep net_speeder | grep -vc grep)
if [[ $net_speeder -eq 0 ]]; then
    nohup /root/net-speeder-master/net_speeder eth0 "ip" > /dev/null 2>&1 &
fi