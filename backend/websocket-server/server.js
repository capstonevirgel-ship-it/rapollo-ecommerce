import { Server } from 'socket.io';
import { createServer } from 'http';
import axios from 'axios';
import dotenv from 'dotenv';

// Load environment variables
dotenv.config();

const PORT = process.env.PORT || 6001;
const LARAVEL_API_URL = process.env.LARAVEL_API_URL || 'http://localhost:8000/api';
const CORS_ORIGIN = process.env.CORS_ORIGIN || 'http://localhost:3000';

// Create HTTP server
const httpServer = createServer();

// Create Socket.io server with CORS configuration
const io = new Server(httpServer, {
  cors: {
    origin: CORS_ORIGIN,
    methods: ['GET', 'POST'],
    credentials: true
  },
  transports: ['websocket', 'polling']
});

// Store user connections: userId -> Set of socketIds
const userConnections = new Map();

// Validate token with Laravel API
async function validateToken(token) {
  try {
    const response = await axios.post(
      `${LARAVEL_API_URL}/websocket/validate-token`,
      {},
      {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      }
    );
    
    if (response.data && response.data.user) {
      return response.data.user;
    }
    return null;
  } catch (error) {
    console.error('Token validation error:', error.message);
    return null;
  }
}

// Handle new connections
io.on('connection', async (socket) => {
  console.log('New connection attempt:', socket.id);

  // Get token from query string
  const token = socket.handshake.query.token;

  if (!token) {
    console.log('No token provided, disconnecting:', socket.id);
    socket.emit('error', { message: 'Authentication required' });
    socket.disconnect();
    return;
  }

  // Validate token
  const user = await validateToken(token);

  if (!user) {
    console.log('Invalid token, disconnecting:', socket.id);
    socket.emit('error', { message: 'Invalid authentication token' });
    socket.disconnect();
    return;
  }

  // Store connection
  const userId = user.id;
  if (!userConnections.has(userId)) {
    userConnections.set(userId, new Set());
  }
  userConnections.get(userId).add(socket.id);

  console.log(`User ${userId} (${user.user_name}) connected. Socket: ${socket.id}`);
  console.log(`Total connections for user ${userId}: ${userConnections.get(userId).size}`);

  // Send connection confirmation
  socket.emit('connected', {
    message: 'WebSocket connection established',
    user: {
      id: user.id,
      user_name: user.user_name,
      role: user.role
    }
  });

  // Handle disconnection
  socket.on('disconnect', () => {
    console.log(`Socket ${socket.id} disconnected`);
    
    if (userConnections.has(userId)) {
      userConnections.get(userId).delete(socket.id);
      
      // Remove user entry if no connections left
      if (userConnections.get(userId).size === 0) {
        userConnections.delete(userId);
      }
    }
  });

  // Handle ping/pong for keepalive
  socket.on('ping', () => {
    socket.emit('pong');
  });
});

// HTTP endpoint to receive notifications from Laravel
httpServer.on('request', async (req, res) => {
  // Enable CORS
  res.setHeader('Access-Control-Allow-Origin', CORS_ORIGIN);
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
  res.setHeader('Access-Control-Allow-Credentials', 'true');

  if (req.method === 'OPTIONS') {
    res.writeHead(200);
    res.end();
    return;
  }

  if (req.method === 'POST' && req.url === '/notify') {
    let body = '';
    
    req.on('data', chunk => {
      body += chunk.toString();
    });

    req.on('end', async () => {
      try {
        const data = JSON.parse(body);
        const { userId, notification } = data;

        if (!userId || !notification) {
          res.writeHead(400, { 'Content-Type': 'application/json' });
          res.end(JSON.stringify({ error: 'Missing userId or notification' }));
          return;
        }

        // Get all connections for this user
        const connections = userConnections.get(userId);

        if (!connections || connections.size === 0) {
          console.log(`No active connections for user ${userId}`);
          res.writeHead(200, { 'Content-Type': 'application/json' });
          res.end(JSON.stringify({ message: 'User not connected', delivered: false }));
          return;
        }

        // Broadcast notification to all user's connections
        let delivered = false;
        connections.forEach(socketId => {
          const socket = io.sockets.sockets.get(socketId);
          if (socket) {
            socket.emit('new_notification', {
              type: 'new_notification',
              notification: notification
            });
            delivered = true;
          }
        });

        console.log(`Notification sent to user ${userId} (${connections.size} connection(s))`);
        
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ 
          message: 'Notification sent', 
          delivered,
          connections: connections.size 
        }));

      } catch (error) {
        console.error('Error processing notification:', error);
        res.writeHead(500, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ error: 'Internal server error' }));
      }
    });
  } else if (req.method === 'POST' && req.url === '/notify-count') {
    let body = '';
    
    req.on('data', chunk => {
      body += chunk.toString();
    });

    req.on('end', async () => {
      try {
        const data = JSON.parse(body);
        const { userId, type, count } = data;

        if (!userId || !type || count === undefined) {
          res.writeHead(400, { 'Content-Type': 'application/json' });
          res.end(JSON.stringify({ error: 'Missing userId, type, or count' }));
          return;
        }

        // Get all connections for this user
        const connections = userConnections.get(userId);

        if (!connections || connections.size === 0) {
          console.log(`No active connections for user ${userId}`);
          res.writeHead(200, { 'Content-Type': 'application/json' });
          res.end(JSON.stringify({ message: 'User not connected', delivered: false }));
          return;
        }

        // Broadcast count update to all user's connections
        let delivered = false;
        connections.forEach(socketId => {
          const socket = io.sockets.sockets.get(socketId);
          if (socket) {
            socket.emit('admin_count_update', {
              type: type,
              count: count
            });
            delivered = true;
          }
        });

        console.log(`Count update sent to user ${userId} (${connections.size} connection(s)): ${type} = ${count}`);
        
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ 
          message: 'Count update sent', 
          delivered,
          connections: connections.size 
        }));

      } catch (error) {
        console.error('Error processing count update:', error);
        res.writeHead(500, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ error: 'Internal server error' }));
      }
    });
  } else {
    res.writeHead(404, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ error: 'Not found' }));
  }
});

// Start server
httpServer.listen(PORT, () => {
  console.log(`🚀 WebSocket server running on port ${PORT}`);
  console.log(`📡 Laravel API URL: ${LARAVEL_API_URL}`);
  console.log(`🌐 CORS Origin: ${CORS_ORIGIN}`);
});

// Graceful shutdown
process.on('SIGTERM', () => {
  console.log('SIGTERM received, shutting down gracefully...');
  httpServer.close(() => {
    console.log('WebSocket server closed');
    process.exit(0);
  });
});

