# Analzing the Wordpress Knowledgebase

## Introduction

The project is about analyzing the content of a knowledge base in a wordpress installation.
The knowledge base plugin that is used in the project is [Echo Knowledge Base](https://www.echoknowledgebase.com/).
Basic features of the knowledge base are usable for free. However, there are many options that
need to be paid for, such as article exports.

Our knowledge base articles vary from a short FAQ article into long elaborations about
a topic. The goal of this project is to classify the articles, bring then into a common structure
and also check if the content is up to date. Also, we want to export the
taxonomy, such as categories and keywords.

## Preparation

I had a ready to use Mariadb dump from the nightly backup of our Wordpress installation. Therefore,
I was able to load the data easily in this project into a local database.

### Prequisites

To follow roughly this guide, a Docker installation is required, and PHP running on your local
system.

You may also use the PHP scripts directly on your Wordpress installation, using the live database.

### Prepare the project

The setup and configuration is located in the `config.ini` and the `comppose.yaml`. Because
this is an internal project, database names and credtenials are visible in the config files and
these are committed in the repository.

To start the database container and a frontend that can be used via the webbrowser run:

```
docker compose up -d
```

Upon the first start, the containers need to be pulled and the startup needs some time. Any
subsequent starts are faster then. When everything is running it should look like this:

```
CONTAINER ID   IMAGE         COMMAND                  CREATED          STATUS           PORTS                                         NAMES
a784a5608d15   adminer       "entrypoint.sh docke…"   21 seconds ago   Up 20 seconds    0.0.0.0:8080->8080/tcp, [::]:8080->8080/tcp   kb-adminer-1
0e1ca1921f0a   mariadb:11.4  "docker-entrypoint.s…"   21 seconds ago   Up 20 seconds    0.0.0.0:3306->3306/tcp, [::]:3306->3306/tcp   kb-db-1
```

Load the data rom the dump into the db:

```
docker cp db-dump-2025-08-26-03-10.sql kb-db-1:.
docker exec kb-db-1 bash -c 'mariadb -u foo --password=bar -D wp < db-dump-2025-08-26-03-10.sql'
```

With the adminer (a webfrontend for MariaDB) you may check at http://localhost:8080 that the data
is in the database.

For the scripts to run, the composer installs need to be done:

```
composer install
```

That should create a vendor directory with the necessary packages installed.

## Start analyzing

To check that everything works, you may run `php check.php` that should give you a list
of articles from the knowledge base. If you have no knowledge base articles from the plugin,
mentioned above, in your Wordpress installation, then no articles show up.

Otherwise you get a list like:

```
$ php check.php 
105
1234 Aktivation
2345 Digital Badges
5395 Constructive Alignment
7287 DACUM
...
```

We have 130 articles at the moment in our knowledge base.

To fetch information about a single article, use the id like `php article.php 1234`
with a short overview of the main information about the article or get the
whole dump via `php article.php 1234 --dump`.
