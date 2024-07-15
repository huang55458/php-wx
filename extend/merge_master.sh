#! /bin/bash
# 下载 git，并配合 alias 使用
set -e
echo "脚本用于当前需求本地仓库中只有一次提交内容，合并master分支最新提交并重新推送"

function check_clean() {
    local context=$(git status | grep 'working tree clean')
    if [[ $context == '' ]]; then
        echo '请检查是否存在未提交的文件！'
        exit 1
    fi
}

function get_master_name() {
	read -p '
请选择需要合并的分支：
  1: master（默认）
  2: Acc_master
' num
	case $num in
        1)  merge_master_name=master
        ;;
        2)  merge_master_name=Acc_master
        ;;
        *)  merge_master_name=master
        ;;
    esac
}

function check() {
	local tmp=$(git log | grep Author | head -n 2 | awk -F: '{ print $2}')
	local name1=$(echo $tmp | awk '{print $1}')
	local name2=$(echo $tmp | awk '{print $3}')
	if [[ $name1 = $name2 ]]; then
		#statements
		echo "连续两次为同一个用户提交，请检查是否合并提交"
		exit 1
	fi
}

function pull() {
	local current_branch=$(git branch | grep '*' | awk '{print $2}')
	read -p "请输入需要操作的分支名(${current_branch}):" branch
	if [[ -z $branch ]]; then
	    branch=$current_branch
	fi
	check_branch $branch
	git checkout $merge_master_name > /dev/null
	git pull > /dev/null || { echo "拉取最新master代码失败"; git checkout "$branch" > /dev/null; exit 1; }
	echo "$merge_master_name 分支更新成功"
	git checkout $branch > /dev/null
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
	    echo "当前分支不存在，请检查分支是否输入正确！"
	    exit 1
	fi
	if [[ "$1" =~ 'master' || "$1" =~ 'develop' ]]; then
	    echo "当前分支包含master或develop，请检查分支是否输入正确！"
	    exit 1
	fi
}

function stash() {
    local context=$(git stash apply | grep 'CONFLICT')
    if [[ $context =~ 'CONFLICT' ]]; then
        echo '存在合并冲突，脚本退出！'
        echo context | sed 's/CONFLICT/\n CONFLICT/g' | grep CONFLICT
        exit 1
    fi
}

function commit() {
	local log=$(echo "$(git log --oneline --no-merges | head -n 1)" | sed 's/.\{10\}//')
	git reset HEAD^
	git stash
	git merge $merge_master_name
	stash
	echo -e "\033[1m默认为上次提交消息：${log}\033[0m"
	read -p '请输入提交信息(回车使用默认值):' message
	if [[ -z $message ]]; then
	    message=$log
	fi
	git commit -a -m $message
	check_branch $branch
	read -p "将$branch分支强制推送远端，是否继续操作（y）：" do_push
	if [[ -z $do_push || $do_push == y ]]; then
	    git push -f origin $branch
	fi
}

check_clean
get_master_name
pull
check
commit