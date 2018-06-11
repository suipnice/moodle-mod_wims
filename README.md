This plugin allows one to insert WIMS classes in Moodle classes.
It requires a WIMS server to have been setup and correctly configured.
Information regarding extended configuration for the WIMS server required for this plugin to work can be found below:


WIMS Configuration files
------------------------

You will find in the "wims-config-templates" directory a set of 4 configuration files that need to be setup on the WIMS server to enable connections from Moodle.

The configuration files must be put in the WIMS server in the directory:

    ../wims/log/classes/.connections/

The files are called:

* moodle
* moodlehttps
* moodlejson
* moodlejsonhttps

In these files, one needs to specify a number of parameters by hand including:

*     **ident_site** => which must include the ip address that the Moodle server connects from
*     **ident_password** => which should be a real password and will need to be provided as a parameter to the moodle plugin

