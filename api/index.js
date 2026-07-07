import http from 'http';
import https from 'https';

export default async function handler(req, res) {
  // Extract the target URL from the query string (e.g., /api/proxy?url=https://example.com)
  const targetUrlString = req.query.url;

  if (!targetUrlString) {
    return res.status(400).json({ error: 'Missing "url" query parameter.' });
  }

  try {
    const targetUrl = new URL(targetUrlString);
    
    // Choose the correct module based on the target protocol
    const transport = targetUrl.protocol === 'https:' ? https : http;

    const options = {
      hostname: targetUrl.hostname,
      port: targetUrl.port || (targetUrl.protocol === 'https:' ? 443 : 80),
      path: targetUrl.pathname + targetUrl.search,
      method: req.method,
      headers: {
        ...req.headers,
        // Host header must match the target destination server
        host: targetUrl.hostname, 
      },
    };

    // Forward the request to the target destination
    const targetRequest = transport.request(options, (targetResponse) => {
      // Pass along the destination status code and headers
      res.writeHead(targetResponse.statusCode, targetResponse.headers);
      
      // Stream the response body data directly back to the client
      targetResponse.pipe(res, { end: true });
    });

    // Handle connection errors
    targetRequest.on('error', (err) => {
      res.status(502).json({ error: 'Bad Gateway', details: err.message });
    });

    // Pipe any incoming request body (like POST data) to the destination
    req.pipe(targetRequest, { end: true });

  } catch (error) {
    return res.status(400).json({ error: 'Invalid URL format provided.' });
  }
}
