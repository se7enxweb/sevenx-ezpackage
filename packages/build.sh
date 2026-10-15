#!/bin/bash

# Left out of every package: version control folders and files (.git, .gitignore, .gitattributes,
# .gitmodules, .svn, .hg), editor and operating system leftovers (.DS_Store, .idea, .vscode, backups ending
# in ~ such as *.php~, *.swp) and node_modules. An installed package needs none of them: the package
# handlers install the files below each package folder, and these only come along from the working copies
# of the bundled extensions. tar applies each name at any depth, a folder with everything below it.
EXCLUDES=(
    --exclude='.git'
    --exclude='.gitignore'
    --exclude='.gitattributes'
    --exclude='.gitmodules'
    --exclude='.svn'
    --exclude='.hg'
    --exclude='.DS_Store'
    --exclude='.idea'
    --exclude='.vscode'
    --exclude='node_modules'
    --exclude='*~'
    --exclude='*.swp'
)


echo "Starting build @" . `pwd`;
##cd "/web/vh/se7enx/alpha.se7enx.com/src/sevenx/packages/";

for folder in *; do
    if [ ${folder} != "release*" ]; then

    if [ -d ${folder} ]; then
       cd "${folder%/}";
       #zip -r "${folder%/}" "${folder%/}"
       #echo "tar -zcf ../${folder}.ezpkg *;";
       tar -zcf ../${folder}.ezpkg "${EXCLUDES[@]}" *;
       cd ../;
    fi

    fi
done


exit
