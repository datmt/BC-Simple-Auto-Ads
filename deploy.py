import os
import subprocess
import sys, getopt
import pysftp
import getpass

current_path = os.getcwd() + "/"
exclude_pro = current_path + "exclude-pro.txt"
exclude_free = current_path + "exclude-free.txt"

test_path = "/Applications/MAMP/htdocs/test/wp-content/plugins/bc-simple-auto-ads"


free_path = "/Users/myn/Documents/wpsvn/bc-simple-auto-ads/trunk"

#Copy to pro
def copy_pro():
    print("copy nothing")

def copy_free():
    #delete current folders first
    subprocess.call(['/bin/sh', '-c', "rm -rf {0}".format(test_path)])
    subprocess.call(['/bin/sh', '-c', "rm -rf {0}".format(free_path)])


    test_command = "rsync -avz --exclude-from {0} {1} {2}".format(exclude_free, current_path, test_path)
    deploy_command = "rsync -avz --exclude-from {0} {1} {2}".format(exclude_free, current_path, free_path)

    #execute both
    subprocess.call(['/bin/sh', '-c', test_command])
    subprocess.call(['/bin/sh', '-c', deploy_command])
    print("copy free complete to {0}".format(free_path))


def upload_ftp(username, password):
    print("upload nothing")

def main(argv):
    copy_free()


if __name__ == "__main__":
    main(sys.argv[1:])
















