# Visidea
A WooCommerce Plugin for Recommendations.

## Project Structure and Functioning
``` bash
.
├── admin
│   └── class-visidea-admin.php            Admin Class - Implementation of admin callbacks
├── data                                        Default data dump folder
│   ├── items_dump.csv                          Result of a cronjob
│   ├── item_user_interactions.csv              Dynamically updated
│   └── users_dump.csv                          Result of a cronjob
├── includes
│   ├── class-visidea-activator.php        Activation Procedures
│   ├── class-visidea-deactivator.php      Deactivation Procedures
│   ├── class-visidea-loader.php           Filter and action hooks loader
│   └── class-visidea.php                  Main Visidea class
├── index.php
├── public
│   └── class-visidea-public.php           Public Class - Implementation of public callbacks
├── README.md                                   This file
├── visidea.php                            Main Bootstrap file

```
The project is recognized by WordPress by its main Bootstrap file, ``visidea.php``.
That file defines this plugin's version, creates custom cronjob intervals and
registers the activation and deactivation hooks, namely ``includes/class-visidea-activator.php`` and ``includes/class-visidea-deactivator.php``.
It then proceeds to initialize the main Visidea class ``includes/class-visidea.php``.


The activator class initializes all _options_, which are values saved to be used
throughout all the classess: databases id, public token, filenames and file headers; it starts the cronjob as well.
The deactivator class cleans all files in the ``data`` folder and stops the cronjob.

The admin class ``admin/class-visidea-admin.php`` implements the methods for cronjob dumps and
new product/new user dumps in ``data/items_dump.csv`` and ``data/users_dump.csv`` respectively.
The public class ``public/class-visidea-public.php`` implements methods for appending
user-item interactions to the already initialized ``data/item_user_interactions.csv`` file.
The loader class ``include/class-visidea-loader.php`` manages all hooks registrations.

The main visidea class ``includes/class-visidea-class.php`` brings it all together,
initializing admin, public and loader classess, adding the implemented methods in admin/public to
the hooks register of the loader class.
