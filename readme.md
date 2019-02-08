This repository is a clone made from the official Vicidial SVN trunk (revision 3063 as of 2019-02-07). It is meant to be a public Vicidial GIT alternative to the common SVN code the Vicidial group updates.

This version will (mostly) stick to the official source code. However, I might include modifications to some key files which have not been accepted by Matt Florell, but which we find useful for our case uses.

The original source code can be grabbed from svn://svn.eflo.net:3690/agc_2-X/trunk

### How to install
If you have previous experience with the way Vicidial source code distributes, switching to this repository is pretty straightforward. For example, in Vicibox you can do this:

```
mkdir /usr/src/astguiclient-git
cd /usr/src/astguiclient-git
git clone https://github.com/ccabrerar/vicidial trunk
cd trunk
perl install.pl
```
And for future updates:
```
cd /usr/src/astguiclient-git/trunk
git pull .
perl install.pl
```
This way, you should end with the official code inside ```/usr/src/astguiclient/trunk``` and the modified one in ```/usr/src/astguiclient-git/trunk```. Depending on which one you install, you will get that one working.
