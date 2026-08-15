# Project instructions

## Deployment

- Deployment is already fully configured for this project.
- When the user says «выкладывай», «на сервер», or otherwise asks to deploy, do not search for deployment configuration and do not ask how to deploy.
- Treat deployment as the established workflow: verify the requested changes, commit only the relevant project files, and push `main` to `origin`. The existing project infrastructure handles the server deployment.
- Never include local artifact directories such as `output/` or `tmp/` in a deployment commit unless the user explicitly requests them.
