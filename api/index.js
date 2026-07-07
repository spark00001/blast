export default async function handler(req, res) {
  const TARGET_URL = 'https://spark00001.github.io/spark/blast.html'; // Change this to your target site
  
  // Reconstruct the target path
  const targetPath = req.url === '/' ? '' : req.url;
  const destination = `${TARGET_URL}${targetPath}`;

  try {
    const response = await fetch(destination, {
      method: req.method,
      headers: { ...req.headers, host: new URL(TARGET_URL).host },
    });
    
    const data = await response.text();
    res.status(response.status).send(data);
  } catch (error) {
    res.status(500).send('Proxy Error');
  }
}
