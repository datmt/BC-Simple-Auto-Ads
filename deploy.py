import os
import subprocess
import sys, getopt
import pysftp
import getpass

current_path = os.getcwd() + "/"
ftp_path = '/var/www/binarycarpenter.com/html/archive/woo-table' #don't include a trailing slash
ftp_host = 'wpleadplus.com'
exclude_pro = current_path + "exclude-pro.txt"
exclude_free = current_path + "exclude-free.txt"

test_path = "/var/www/html/test/wp-content/plugins/woo-table-pro"

pro_path = "/var/www/html/releases/woo-table-pro"
free_path = "/var/www/html/wpsvn/free-product-table-for-woocommerce/trunk"

#Copy to pro
def copy_pro():
    #delete current folders first
    subprocess.call(['/bin/sh', '-c', "rm -rf {0}".format(test_path)])
    subprocess.call(['/bin/sh', '-c', "rm -rf {0}".format(pro_path)])

    test_command = "rsync -avz --exclude-from '{0}' {1} {2}".format(exclude_pro, current_path, test_path)
    deploy_command = "rsync -avz --exclude-from '{0}' {1} {2}".format(exclude_pro, current_path, pro_path)
    
    #execute both
    subprocess.call(['/bin/sh', '-c', test_command]);
    subprocess.call(['/bin/sh', '-c', deploy_command]);
    print("copy pro complete to {0}".format(pro_path))

def copy_free():
    #delete current folders first
    subprocess.call(['/bin/sh', '-c', "rm -rf {0}".format(test_path)])
    subprocess.call(['/bin/sh', '-c', "rm -rf {0}".format(free_path)])


    test_command = "rsync -avz --exclude-from {0} {1} {2}".format(exclude_free, current_path, test_path)
    deploy_command = "rsync -avz --exclude-from {0} {1} {2}".format(exclude_free, current_path, free_path)

    #execute both
    subprocess.call(['/bin/sh', '-c', test_command]);
    subprocess.call(['/bin/sh', '-c', deploy_command]);
    print("copy free complete to {0}".format(free_path))
    

def upload_ftp(username, password):
    #before uploading, copy the files to pro first
    copy_pro()

    c1 = pysftp.CnOpts()
    c1.hostkeys = None
     
    with pysftp.Connection(host=ftp_host, username=username, password=password, cnopts=c1) as sftp:
        print('connection ok')
        for root, dirs, files in os.walk(pro_path):
            for file in files:
                local_file = os.path.join(root, file)
                remote_file = local_file.replace(pro_path, ftp_path) 
                print("remote_file: " + remote_file)
                #upload files
                print("putting {0} online".format(local_file))
                sftp.put(local_file, remote_file)

def main(argv):
    action = '' #copy or upload, if the action is pro, then app will ask for version (free or pro), if the action is upload, app will ask for username and password
    version = '' # free or pro

    action = input("what do you want to do? (copy/upload) \n").strip()
    if (action == "copy"):
        print("user wants to copy")
        version = input("Copy free or pro? \n")
        if (version == "pro"):
            copy_pro()
        elif (version == "free"):
            copy_free()
        else:
            print("your version name is not valid")
            return
    elif (action =="upload"):
        username = input("what is your ftp username? \n")
        password = getpass.getpass("password? \n")
        print("user name is " + username + " and password " + password)
        upload_ftp(username, password)

if __name__ == "__main__":
    main(sys.argv[1:])
















