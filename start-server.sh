#!/bin/bash

echo "🚀 Starting Sistem Informasi Bimbel Server..."
echo ""

# Check if Python is installed
if ! command -v python3 &> /dev/null; then
    echo "❌ Python3 tidak ditemukan. Silakan install Python3 terlebih dahulu."
    exit 1
fi

# Start Python HTTP Server
echo "✅ Starting HTTP Server on port 8080..."
echo "📍 Local URL: http://localhost:8080"
echo ""
echo "Press Ctrl+C to stop the server"
echo ""

python3 -m http.server 8080
