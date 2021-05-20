# BuddyPress

[![Build Status](https://api.travis-ci.org/buddypress/BuddyPress.svg?branch=master)](https://travis-ci.org/github/buddypress)

[![Unit Tests](https://github.com/buddypress/buddypress/workflows/Unit%20Tests/badge.svg)](https://github.com/buddypress/buddypress/actions?query=workflow%3A%22Unit+Tests%22+branch%3Amaster)

Welcome to the BuddyPress development repository! This repository is a mirror of our development [SVN repository](https://buddypress.svn.wordpress.org/). Please do not send pull requests here, instead submit patches to our [SVN repository](https://buddypress.trac.wordpress.org/). Check out the [Participate & contribute](https://codex.buddypress.org/participate-and-contribute/) page of our Codex for information about how to open bug reports, contribute patches, test changes, write documentation, or get involved in any way you can.

* [Getting Started](#getting-started)
* [Credentials](#credentials)

## Getting Started

BuddyPress is a WordPress plugin to power you community site. It is a PHP, MySQL, and JavaScript based project, and uses Node for its JavaScript dependencies. A local development environment is available to quickly get up and running.

You will need a basic understanding of how to use the command line on your computer. This will allow you to set up the local development environment, to start it and stop it when necessary, and to run the tests.

You will need Node and npm installed on your computer. Node is a JavaScript runtime used for developer tooling, and npm is the package manager included with Node. If you have a package manager installed for your operating system, setup can be as straightforward as:

* macOS: `brew install node`
* Windows: `choco install node`
* Ubuntu: `apt install nodejs npm`

If you are not using a package manager, see the [Node.js download page](https://nodejs.org/en/download/) for installers and binaries.

You will also need [Docker](https://www.docker.com/products/docker-desktop) installed and running on your computer. Docker is the virtualization software that powers the local development environment. Docker can be installed just like any other regular application.

### Development Environment Commands

Ensure [Docker](https://www.docker.com/products/docker-desktop) is running before using these commands.

#### To start the development environment for the first time

```
npm install
npm run wp-env start
```

Your WordPress community site will be accessible at http://localhost:8888. You can see configurations in the `.wp-env.json` file located at the root of the project directory. You can [override](https://developer.wordpress.org/block-editor/packages/packages-env/#wp-env-override-json) these configurations using a `.wp-env.override.json` file located at the root of the project repository.

#### To stop the development environment

You can stop the environment when you're not using it to preserve your computer's power and resources:

```
npm run wp-env stop
```

#### To start the development environment again

Starting the environment again is a single command:

```
npm run wp-env start
```

## Credentials

To login to the site, navigate to http://localhost:8888/wp-admin.

* Username: `admin`
* Password: `password`

To generate a new password (recommended):

1. Go to the Dashboard
2. Click the Users menu on the left
3. Click the Edit link below the admin user
4. Scroll down and click 'Generate password'. Either use this password (recommended) or change it, then click 'Update User'. If you use the generated password be sure to save it somewhere (password manager, etc).
