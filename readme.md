# Vicidial
This repository is a clone made from the official Vicidial SVN trunk (revision 3329 as of 2021-08-21). It is meant to be a public GIT alternative to the common SVN code updated by the Vicidial group.

This version will (mostly) stick to the official source code. However, even though we try to submit official patches to Vicidial through the [Mantis bug tracker](http://www.vicidial.org/VICIDIALmantis), sometimes they get too long to get accepted or don't get accepted at all, so this code will include modifications to some key files which have not been yet accepted by Matt Florell, but which I find useful for our own company's use cases.

The original and unaltered source code can be grabbed by SVN from svn://svn.eflo.net:3690/agc_2-X/trunk.

## Key differences with Vicidial original code

#### Chat functions are *really* disabled if campaign says so
In original code, every agent interface still queries the DB each second to check for new messages, even if the campaign's chat is disabled. This causes extra load on the database for large agent setups.

### Realtime report uses vicidial_live_agents table for pause codes
Vicidial's code queries the ```vicidial_agent_log``` for getting the last pause code. Instead, a lot of performance can be saved if we just use the ```vicidial_live_agents``` table, which is much smaller.

### Avoids performance penalties for some indexes
Some values like ```lead_id``` are wrapped around single quotes (') in the original code, causing them to be treated like strings if values get too big, resulting in big performance penalties. For example, this query:
```
EXPLAIN SELECT * FROM vicidial_list WHERE lead_id = '9999999999';
```
Is much slower than this:
```
EXPLAIN SELECT * FROM vicidial_list WHERE lead_id = 9999999999;
```
By removing these quotes, MariaDB can correctly check its indexes, resulting in much faster results.


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
You may want to look at my [Viciphone 2.0](https://github.com/ccabrerar/viciphone) repository, which is an excellent companion for Vicidial.
