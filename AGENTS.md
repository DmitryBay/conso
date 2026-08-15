# Project instructions

## Deployment

- Deployment is already fully configured for this project.
- When the user says «выкладывай», «на сервер», or otherwise asks to deploy, do not search for deployment configuration and do not ask how to deploy.
- Treat deployment as the established workflow: verify the requested changes, commit only the relevant project files, and push `main` to `origin`.
- Production host: `lalo.craabchee.com`. Application path: `/var/www/luma-concierge`.
- After pushing, connect over SSH as `root`, but run all application commands as the `web` user with `sudo -u web -H`.
- Every deployment that includes or may include database changes must run `php artisan migrate --force` as `web`. Then refresh Laravel caches and restart the queue as `web`.
- Verify the production commit, migration status, queue status, and an HTTP response from the production site before reporting deployment complete.
- Never include local artifact directories such as `output/` or `tmp/` in a deployment commit unless the user explicitly requests them.
