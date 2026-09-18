import { execFileSync } from 'node:child_process';

const wpEnv = (...args: string[]): string => execFileSync('npx', ['wp-env', 'run', 'cli', 'wp', ...args], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'inherit'] }).trim();

export default async function globalSetup(): Promise<void> {
  wpEnv('option', 'update', 'permalink_structure', '/%postname%/');
  const output = wpEnv('post', 'list', '--post_type=page', '--name=health-and-safety-healthcheck', '--field=ID');
  const id = output.split(/\r?\n/).map(line => line.trim()).find(line => /^\d+$/.test(line));
  if (id) {
    wpEnv('post', 'update', id, '--post_status=publish', '--post_content=[acorn_safety_healthcheck]');
  } else {
    wpEnv('post', 'create', '--post_type=page', '--post_status=publish', '--post_name=health-and-safety-healthcheck', '--post_title=Health and Safety Healthcheck', '--post_content=[acorn_safety_healthcheck]');
  }
  wpEnv('rewrite', 'flush', '--hard');

  const url = 'http://localhost:8888/health-and-safety-healthcheck/';
  const response = await fetch(url);
  const html = await response.text();
  if (response.status !== 200 || !html.includes('id="acorn-healthcheck"')) {
    throw new Error(`Healthcheck global setup failed: ${url} returned HTTP ${response.status} and shortcode mount present=${html.includes('id="acorn-healthcheck"')}.`);
  }
}
