Pods Deploy
==========

Deploys Pods configuration to a remote site. Copies Pods setup, Pods Template, Pods Pages and Pods Helpers via the WordPress REST API.

<strong>BETA SOFTWARE</strong>

This plugin is still under development, and might not work as expected or at all. <strong>Please read the security section below before using,</strong> so that you understand why only using this over HTTPS is so strongly recommended. Also, be sure to backup the database of the remote site before beginning a deployment.

### Requirements
This plugin requires that on both sites you activate this plugin and the following dependent plugins:

* [WordPress REST API](https://wordpress.org/plugins/json-rest-api/)
* [Pods](https://wordpress.org/plugins/pods/) version 2.4.3 or later.
* [Pods JSON API](https://github.com/pods-framework/pods-json-api) version 0.2.1 or later.

In addition, pretty permalinks must be active on both sites.

### Instructions
Pods Deploy needs to be setup on both the remote (deploy to) site and the local (deploy from) site. You must manually allow deployments to a site. When you do a public/private key pair is generated. You must supply this key pair and the remote site's API url to the local (deploy from) site.

##### Remote (To) Site
* Go to Pods Admin-> Pods Deploy page, "Allow Deploying To This Site" tab.
* Click the blue "Allow Deployments" button.
* Copy public and private keys.

Note: You can disable deployments from this tab. Doing so clears the keys. Reactivating generates new keys.

#### Local (From) Site
* Activate Pods and Pods Packages component.
* Go to Pods Admin-> Pods Deploy page, "Deploy From This Site" tab.
* Enter the remote site's API url, this is generally the site's URL /wp-json, but it may not be as that URL is variable via a filter.
* Enter the public and secret keys in their fields.
* Click the blue "Deploy" button.
* Magic /should/ happen. When completed, you should see a completed message and a remote site should have the local site's Pods.

<em>If you see any failure messages please</em>: [Please report them](https://github.com/pods-framework/pods-deploy/issues) and include the `var_dump()` of the response that is generated.

### Security
The authentication system is still being evaluated. USE AT YOUR OWN RISK. Authentication is handled via public/private key pair method, which when used without HTTPS is vulnerable to a [MITM attack](http://en.wikipedia.org/wiki/Man-in-the-middle_attack).

It is <strong>STRONGLY ADVISED that deployments be done over HTTPS</strong>. We recommend that you disable deployments on the remote site when not in use.

The authentication system in use here grants access to the endpoints & routes that are required by the deployment process only. Therefore unauthorized authentication *should* not allow unauthorized access to other endpoints, but would still be bad.


### License & Copyright
Copyright 2014  [Pods Foundation, Inc.](http://podsfoundation.org)

Licensed under the terms of the [GNU General Public License version 2](http://www.gnu.org/licenses/gpl-2.0.html) or later. Please share with your neighbor.

The authorization class is inspired by, with light copypasta from the [Easy Digital Downloads](https://easydigitaldownloads.com/) API. Much GPL, very appreciation.



