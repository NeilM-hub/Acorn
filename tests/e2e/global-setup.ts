import { execFileSync } from 'node:child_process';

export default async function globalSetup(): Promise<void> {
  const command = "wp post list --post_type=page --name=health-and-safety-healthcheck --field=ID | head -1";
  const id = execFileSync('npx', ['wp-env', 'run', 'cli', 'sh', '-lc', command], { encoding: 'utf8' }).trim();
  const args = id
    ? ['wp-env', 'run', 'cli', 'wp', 'post', 'update', id, '--post_status=publish', '--post_content=[acorn_safety_healthcheck]']
    : ['wp-env', 'run', 'cli', 'wp', 'post', 'create', '--post_type=page', '--post_status=publish', '--post_name=health-and-safety-healthcheck', '--post_title=Health and Safety Healthcheck', '--post_content=[acorn_safety_healthcheck]'];
  execFileSync('npx', args, { stdio: 'inherit' });
}
