# WIMS classroom as Moodle activity #
![Licence](https://img.shields.io/github/license/suipnice/moodle-mod_wims)
![Moodle Plugin CI](https://github.com/suipnice/moodle-mod_wims/actions/workflows/moodle-ci.yml/badge.svg)

This plugin allows one to insert WIMS classes in Moodle courses.
It requires a WIMS server to have been setup and correctly configured.
Information regarding extended configuration for the WIMS server required for this plugin to work can be found below:


WIMS Configuration files
------------------------

You will find in the "wims-config-templates" directory a set of 2 configuration files that need to be setup on the WIMS server to enable connections from Moodle.

The configuration files must be put in the WIMS server in the directory:

    ../wims/log/classes/.connections/

The files are called:

* moodlejson
* moodlejsonhttps

In these files, one needs to specify a number of parameters by hand including:

* **ident_site** => which must include the ip address that the Moodle server connects from
* **ident_password** => which should be a real password and will need to be provided as a parameter to the Moodle plugin


Moodle Configuration
--------------------

1. Login as administration to install the plugin.
2. Go to: Site administration → Plugins → Install plugins
3. Install plugin from ZIP file by dropping the zip file `moodle-mod_wims-master.zip`
4. Click `continue` in the verification window
5. Click `upgrade Moodle database now` to finish the process.
6. Confirm the upgrade procedure by click in `Continue`

Now, some plugin parameters need to be changed to indicate the associated WIMS server.

1. Go to: Site administration → Plugins → Plugins overview
2. find the `WIMS` plugin and click on `Settings`

In the settings window, modify the following parameters:

* **WIMS Server URL**: put your WIMS server URL (example: `wims-deq.urv.cat/wims/wims.cgi`)
* **WIMS Server connection password**: the password you decide (the same you put in **ident_password** in WIMS config files

Now the administrator tasks are finished.

How works the plugin:
---------------------

You must know that the WIMS classroom is created and maintained from the Moodle server.
If you log in Moodle as teacher, then you will have teacher permisions in the WIMS classroom.
In the same way, if you are student in Moodle, you will connect in the WIMS classroom as student.

Add an activity and choose `WIMS Course`. Then choose the value for the **Activity Name** and this will be the name of the new classroom created in WIMS.

If you create an exam in your WIMS classroom, the marks will be imported automaticaly by the Moodle server.

If you want also the sheets marks to be exported, you have to select the specific sheets in the activity parameters in Moodle.

All marks (exams + selected sheets) will automatically be imported in Moodle every nights.

## License ##

- 2015 Edunao SAS <contact@edunao.com>
- 2018-2019 Université Nice Sophia Antipolis <pi@unice.fr>
- 2020-2024 Université Côte d’Azur <dsi.adm-lms@univ-cotedazur.fr>

This program is free software:
 you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation,
 either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program.
If not, see <http://www.gnu.org/licenses/>.
