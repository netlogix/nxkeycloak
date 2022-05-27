# TYPO3 Extension `nxkeycloak`

[![stability-beta](https://img.shields.io/badge/stability-beta-33bbff.svg)](https://github.com/netlogix/nxkeycloak)
[![TYPO3 V11](https://img.shields.io/badge/TYPO3-11-orange.svg)](https://get.typo3.org/version/11)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.4-8892BF.svg)](https://php.net/)
[![GitHub CI status](https://github.com/netlogix/nxkeycloak/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/netlogix/nxkeycloak/actions)

This extension allows backend logins using a keycloak server.

## Features

* login via a configurable Keycloak server
* create backend user records for new users (currently admin-only)
* login existing (non-keycloak) user if email address matches

## Missing Features

* terminate Keycloak session on TYPO3 logoff
* periodically check if Keycloak session is still active (and terminate TYPO3 session if not)
* fetch group config from Keycloak to create non-admin users
* fetch additional user data to decide if the user is allowed to log-in in a specific TYPO3 applications

## Configuration

### Keycloak
This extension needs a configured client in Keycloak. Go to your desired realm and create a new client using these 
configuration values:
* Client ID: create an ID and take note for later use (use e.g. `uvex-typo3`)
* Client Protocol: `openid-connect`
* Access Type: `confidential`
* enable `Standard Flow`
* add these redirect URLs:
  * `https://<TYPO3-domain>/typo3/*`

Save the client then go to `Credentials` and copy the secret for later use.

### TYPO3
This extension needs the following configuration values set in Install Tool:
* clientId: the ID of the client created in Keycloak
* clientSecret: the secret used to authenticate the client
* host: the host of the Keycloak server. Must include the protocol and port (e.g. `https://keycloak.netlogix.de:8080`)
* realm: the realm containing user data for this application

Note: Both clientID and clientSecret can be set using environment variables (`NXKEYCLOAK_CLIENTID` and `NXKEYCLOAK_CLIENTSECRET`) as well.
Values set in Install Tool will be preferred in any case.