##Set-up Instructions

Generally, the steps to setting up are:

1. [Generate an SSH key on NextGen](#1-generate-an-ssh-key-on-nextgen)
2. [Add the key to GitHub Enterprise](#2-add-the-key-to-github-enterprise)
3. [Copy `autodeploy.php` to your web root](#3-copy-autodeploy-to-your-web-root)
4. [Configure autodeploy](#4-configure-autodeploy)
5. [Set up webhooks in GitHub](#5-set-up-webhooks-in-github)

###1. Generate an SSH key on NextGen
A general tutorial for generating SSH keys can be found [here](https://help.github.com/articles/generating-ssh-keys/).

What follows is specific to JPL and assumes you're installing on NextGen and using JPL's GitHub Enterprise. 

Open a terminal and type the following commands (replacing terms in ALLCAPS with the appropriate values):
```ssh
 ssh YOURUSERNAME@webhosting-login01.jpl.nasa.gov
```

This logs you in to NextGen--you'll have to type your Pin + RSA token as the password. Once you're logged in, you *must* switch user to the name of your site:
```ssh
 sudo become YOURSITEHOSTNAME
```

Then you can create the key with this command:
```ssh
 ssh-keygen -t rsa -C "YOURUSERNAME@jpl.nasa.gov"
```

The passphrase *must* be blank. Type `enter` twice to set it as blank. Next change directory into the (invisible) `.ssh` folder that gets created when you run the previous command, and open `id_rsa.pub` so you can copy the key out of it. Here's how to do that with `vi`:
```ssh
 cd /websites/YOURSITEHOSTNAME/private/.ssh
 vi id_rsa.pub
```
Copy the contents of the file into a temporary text file. (You can use your cursor to drag-select and then copy.) To exit the `vi` editor, type:
```ssh
 :exit
```
Congrats, you've got your private key in your clipboard! Keep it secret, keep it safe! 

###2. Add the key to GitHub Enterprise
Log in to GitHub Enterprise and go to Settings > SSH Keys > Add SSH Key. Paste the new key in. Back in terminal again type to test:
```ssh
ssh -T git@github.jpl.nasa.gov
```
If you did everything right, it should output something like this, or possibly only the last line:
```
The authenticity of host 'github.jpl.nasa.gov (128.149.186.45)' can't be established.
RSA key fingerprint is a5:e4:e5:d5:be:f4:6b:a3:d2:09:50:5a:d3:a6:0f:b7.
Are you sure you want to continue connecting (yes/no)? yes
Warning: Permanently added 'github.jpl.nasa.gov,128.149.186.45' (RSA) to the list of known hosts.
Hi YOURUSERNAME! You've successfully authenticated, but GitHub does not provide shell access.
```
If not, something is wrong, do not pass GO, do not continue until you've figured out what's going on. 

###3. Copy autodeploy to your web root
Copy `autodeploy.php` from this repo onto NextGen in your web root (e.g. `www`). 

###4. Configure autodeploy
Open `autodeploy.php` and edit the variables in the top.

###5. Set up a webhook in GitHub
Go to GitHub > **YOURPROJECT** > Settings > Webhooks & Services > Payload URL. Insert the URL to `autodeploy.php`.

Now `push` a change to `master` on GitHub and see it automagically appear on your NextGen server!
