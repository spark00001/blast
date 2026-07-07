import http from 'http';
import https from 'https';

export default async function handler(req, res) {
  const targetUrlString = req.query.url; //

  if (!targetUrlString) {
    return res.status(400).json({ error: 'Missing "url" query parameter.' }); //
  }

  try {
    const targetUrl = new URL(targetUrlString);
    const transport = targetUrl.protocol === 'https:' ? https : http;

    // Filter headers to avoid sending broken metadata
    const filteredHeaders = { ...req.headers };
    delete filteredHeaders.host;
    delete filteredHeaders.connection;

    const options = {
      hostname: targetUrl.hostname,
      port: targetUrl.port || (targetUrl.protocol === 'https:' ? 443 : 80),
      path: targetUrl.pathname + targetUrl.search,
      method: req.method,
      headers: {
        ...filteredHeaders,
        host: targetUrl.hostname, // Must point to target
      },
    };

    const targetRequest = transport.request(options, (targetResponse) => {
      // Pass headers and status directly back to the client
      res.writeHead(targetResponse.statusCode, targetResponse.headers);
      targetResponse.pipe(res, { end: true });
    });

    targetRequest.on('error', (err) => {
      if (!res.writableEnded) {
        res.status(502).json({ error: 'Bad Gateway', details: err.message });
      }
    });

    // FIX: Safely pass the request body text/json instead of raw streaming req
    if (req.body) {
      const bodyData = typeof req.body === 'object' ? JSON.stringify(req.body) : req.body;
      targetRequest.write(bodyData);
    }

    targetRequest.end();

  } catch (error) {
    return res.status(400).json({ error: 'Invalid URL format provided.' });
  }
}
