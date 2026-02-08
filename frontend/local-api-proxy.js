#!/usr/bin/env node
/**
 * Proxy local : /api -> backend déployé (suit les 307, évite CORS).
 * Node 18+ requis. Lancer : npm run start:proxy
 */

const http = require('http');
const BACKEND = 'https://academy.clouddevfusion.com';
const PORT = 8080;

function getRequestBody(req) {
  return new Promise((resolve) => {
    const chunks = [];
    req.on('data', (chunk) => chunks.push(chunk));
    req.on('end', () => resolve(Buffer.concat(chunks)));
  });
}

const server = http.createServer(async (req, res) => {
  const pathOnly = (req.url || '').split('?')[0];
  if (!pathOnly.startsWith('/api')) {
    if (pathOnly === '/' || pathOnly === '') {
      res.writeHead(200, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ ok: true, message: 'Proxy running', backend: BACKEND }));
      return;
    }
    res.writeHead(404);
    res.end();
    return;
  }

  const backendUrl = new URL(BACKEND);
  const url = BACKEND + (req.url || '');
  const headers = {};
  const skipReq = ['host', 'connection', 'content-length'];
  for (const [k, v] of Object.entries(req.headers)) {
    if (v && !skipReq.includes(k.toLowerCase())) headers[k] = v;
  }
  headers['host'] = backendUrl.host;

  let body = null;
  if (req.method !== 'GET' && req.method !== 'HEAD') {
    body = await getRequestBody(req);
    if (body.length) headers['content-length'] = String(body.length);
  }

  console.log(req.method, req.url, '->', url);
  try {
    const response = await fetch(url, { method: req.method, headers, redirect: 'follow', body: body && body.length ? body : undefined });
    const resBody = await response.arrayBuffer();
    const resHeaders = {};
    ['transfer-encoding', 'connection'].forEach(k => {});
    response.headers.forEach((v, k) => {
      if (!['transfer-encoding', 'connection'].includes(k.toLowerCase())) resHeaders[k] = v;
    });
    res.writeHead(response.status, resHeaders);
    res.end(Buffer.from(resBody));
  } catch (err) {
    const msg = err.message || String(err);
    const code = err.code || '';
    console.error('Proxy error:', code, msg);
    res.writeHead(502, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ error: 'Proxy error', message: msg, code }));
  }
});

server.listen(PORT, () => {
  console.log(`Proxy: http://localhost:${PORT}/api -> ${BACKEND}/api`);
});
