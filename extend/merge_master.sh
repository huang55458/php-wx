#! /bin/bash
# 下载 git，并配合 alias 使用

function check_clean() {
    context=$(git status | grep 'working tree clean')
    if [[ $context == '' ]]; then
        echo '请检查是否存在未提交的文件！'
        exit 1
    fi
}

function check() {
	tmp=$(git log | grep Author | head -n 2 | awk -F: '{ print $2}')
	name1=$(echo $tmp | awk '{print $1}')
	name2=$(echo $tmp | awk '{print $3}')
	if [[ $name1 = $name2 ]]; then
		#statements
		echo "连续两次为同一个用户提交，请检查是否合并提交"
		exit 1
	fi
}

function pull() {
	current_branch=$(git branch | grep '*' | awk '{print $2}')
	git checkout master
	git pull || { echo "拉取最新master代码失败"; exit 1; }
	echo '脚本用于当前需求本地仓库中只有一次提交内容，合并 master 分支最新提交并重新推送'
	read -p "请输入需要操作的分支名(${current_branch}):" branch 
	if [[ -z $branch ]]; then
	    branch=$current_branch
	fi
	check_branch $branch
	git checkout $branch
}

function check_branch() {
	flag=0
	for i in $(git branch)
	do
	  if [ "$i" == "$1" ]; then
	      flag=1
	      break
	  fi
	done
	if [ $flag == 0 ]; then
	    echo "请检查分支是否输入正确"
	    exit 1
	fi
	if [ "$1" == 'master' ]; then
	    echo "请检查分支！"
	    exit 1
	fi
}

function stash() {
    context=$(git stash apply | grep Auto-merging)
    if [[ $context != '' ]]; then
        echo '存在合并冲突，脚本退出！'
        exit 1
    fi
}

function commit() {
	log=$(echo "$(git log --oneline --no-merges | head -n 1)" | sed 's/.\{10\}//')
	git reset HEAD^
	git stash
	git merge master
	stash
	echo -e "\033[1m默认为上次提交消息：${log}\033[0m"
	read -p '请输入提交信息(回车使用默认值):' message
	if [[ -z $message ]]; then
	    message=$log
	fi
	git commit -a -m $message
	git push -f origin $branch
}

check_clean
pull
check
commit
