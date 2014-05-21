Roundcube Plugin Spam Vpopmaild
===============================

Setup
-----

1. Make sure that your installation of vpopmaild is up and running. If you need to know
   how to setup the daemon, see `/doc/README.vpopmaild` in the source folder
   of vpopmail.

2. Copy the files `spam_vpopmaild.php`, `config.inc.php.dist` and the 
   folder `localization` to a folder named `spam_vpopmaild` into the 
   directory `plugins` of your Roundcube installation.

3. Rename `config.inc.php.dist` to `config.inc.php`.

4. Adjust the values in `config.inc.php` to the values that match to
   your setup.

Usage
-----

Access the plugin options via Settings -> Spam protection.

Requirements
------------

Tested with Roundcube 1.0.1 and PHP 5.4.4 but should work with older versions.

License
-------

Roundcube Plugin Spam Vpopmaild let's you adjust the spam preferences via vpopmaild.

Copyright (C) 2014 Simon Plasger

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

See COPYING for details.
