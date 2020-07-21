# Vicidial
This repository is a clone made from the official Vicidial SVN trunk (revision 3270 as of 2020-07-20). It is meant to be a public GIT alternative to the common SVN code updated by the Vicidial group.

This version will (mostly) stick to the official source code. However, even though we try to submit official patches to Vicidial through the [Mantis bug tracker](http://www.vicidial.org/VICIDIALmantis), sometimes they get too long to get accepted or don't get accepted at all, so this code will include modifications to some key files which have not been yet accepted by Matt Florell, but which I find useful for our own company's use cases.

The original and unaltered source code can be grabbed by SVN from svn://svn.eflo.net:3690/agc_2-X/trunk.

## How to install
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
This way, you should end with the official code inside ```/usr/src/astguiclient/trunk``` and the modified GIT one in ```/usr/src/astguiclient-git/trunk```. Depending on which one you install, you will get that one working.

## Related repositories
You may want to look at my [Viciphone](https://github.com/ccabrerar/viciphone) repository, which is an excellent companion for Vicidial.
