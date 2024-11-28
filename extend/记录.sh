git fetch && echo origin/$(git branch | grep '*' | awk '{print $2}') | xargs git reset --hard #更新分支
alias sync_t='rsync -av  ./thinkphp_3.2.4/* root@a.chumeng1.top:/opt/thinkphp_3.2.4'
jq --arg listen 11343 '.listen = $listen' config.json >config1.json
curl -s -L a.chumeng1.top/api/Home/Hello/test
tail -f 23_08_23.log | grep 'You have an error in your SQL syntax'
cat 23_09_27.log | grep ERROR_TRACE | grep -v cmm_pro.muser
sed -i ':a;N;$!ba;s/\n/,/g' demo.txt #换行转逗号
sed -i '/^$/d' demo.txt              # 去掉空行

kafka-topics.sh --zookeeper zookeeper:2181/kafka --topic test --create --partitions 1 --replication-factor 1 #（/kafka 没有会报错，docker-compose 部署host 可用service指定）
kafka-console-producer.sh --topic test --broker-list kafka1:9091
kafka-console-consumer.sh --bootstrap-server kafka1:9091 --topic test --from-beginning

#编译php
./configure --prefix=/home/huangjinxiong/ext/php7 --enable-mysqlnd --with-pdo-mysql --with-pdo-mysql=mysqlnd --enable-bcmath --enable-fpm --with-fpm-user=www-data --with-fpm-group=www-data --enable-mbstring --enable-phpdbg --enable-shmop --enable-sockets --enable-sysvmsg --enable-sysvsem --enable-sysvshm --with-zlib --with-curl --with-pear --with-openssl --enable-pcntl --enable-zts --with-mysqli
cp php.ini-development ~/bin/php-latest/lib/php.ini          #编译目录
cp ./etc/php-fpm.conf.default ./etc/php-fpm.conf             #安装目录
cp ./etc/php-fpm.d/www.conf.default ./etc/php-fpm.d/www.conf #安装目录

#编译时如果出现内存不足：
dd if=/dev/zero of=/opt/images/swap bs=1024 count=2048000
mkswap /opt/images/swap
swapon /opt/images/swap
free -m
swapoff swap
rm -f /opt/images/swap

echo -e '\033[1mtest\033[0m' #高亮显示

for i in 23_12_{23..28}.tgz; do tar -zxvf $i -C ~/ext/log/; done
sed -i '$a\echo 1' start.sh

export XDEBUG_CONFIG='idekey=PHPSTORM' #设置后，命令行执行能进行调试   （windows LTS上执行不会影响windows的环境变量）
#set XDEBUG_CONFIG=idekey=PHPSTORM      #（cmd 上执行有效）

curl -sL -u rw:7ByEfigawaiy 172.30.0.252:9200/_cat/nodes

sed -i -e 's/\r$//' scriptname.sh # 解决 "/bin/bash^M: bad interpreter"

export LD_LIBRARY_PATH="/home/huangjinxiong/ext/lib64" #配置动态库（没有root权限缺少库时）
export PHP_IDE_CONFIG="serverName=ubantu.local"

#编译python
./configure --prefix=/home/huangjinxiong/ext/python --enable-shared --enable-optimizations #(安装完后还配置了动态扩展库)
#编译phpy选项
./configure --with-php-config=/home/huangjinxiong/ext/php8/bin/php-config --with-python-config=/home/huangjinxiong/ext/python/bin/python3-config
./pip3 install tk --prefix=/mnt/e/php-lib -i https://pypi.tuna.tsinghua.edu.cn/simple #下载目录更换，下载临时换源

export PYTHONPATH=/home/chumeng/.local/lib/python3.12/site-packages # pip安装的模块无法加载时（未测试）
#可以直接把模块放入'python安装目录/lib/python3.12/site-packages'，或者在目录下放入模块名.pth,内容为模块路径（如/mnt/e/app/tkinter）

./configure --prefix=/home/huangjinxiong/ext/gcc -enable-checking=release -enable-languages=c,c++ -disable-multilib #gcc编译选项

wget ... -Y on -e "https_proxy=127.0.0.1:10809" #wget使用代理

./configure CFLAGS=-I/usr/local/arm/2.95.3/arm-linux/include LDFLAGS=-L/usr/local/arm/2.95.3/arm-linux/lib  --enable-openssl --enable-sockets  --enable-swoole-curl --enable-cares --enable-swoole-thread   #swoole编译指定库，未测试

/gost-linux-amd64-2.11.5 -L=rtcp://:11223/127.0.0.1:11224

yum groupinstall "Development Tools"