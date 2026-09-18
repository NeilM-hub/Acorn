export class ApiError extends Error {
  constructor(message, status) { super(message); this.status = status; }
}
export async function request(path, options = {}) {
  const response = await fetch(window.acornHealthcheck.rest + path, {
    headers: {'Content-Type': 'application/json'}, ...options,
  });
  const body = await response.json().catch(() => ({}));
  if (!response.ok) throw new ApiError(body.message || 'Unable to save your progress.', response.status);
  return body;
}
