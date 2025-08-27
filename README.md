# Analzing the Wordpress Knowledgebase

## Introduction

The project is about analyzing the content of a Knowledgebase in a wordpress installation.
The knowlede base plugin used in the [Echo Knowledge Base](https://www.echoknowledgebase.com/).
Basic features of the knowledge base are usable for free. However, there are many options that
need to be paid for, such as article exports.

Our knowledge base articles vary from a short FAQ like article into long elaborations about
a topic. We want to classify them, organize the articles that they all follow a certain format
and therefore need to check whether they follow a certain scheme. Also, we want to export the
taxonomy, such as categories and keywords.

## Preparation

I had a ready to use Mariadb dump from the nightly backup. Therefore, I was able to load the
data easily in this project into a local database.

### Prequisites

To follow roughly this guide, a Docker installation is required, and PHP running on your local
system.
You may also use the PHP scripts directly on your Wordpress installation, using the live database.

### Prepare the project

Start the database container and a frontend that can be used via the webbrowser:
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

