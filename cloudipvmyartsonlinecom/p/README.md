# VexNet Key System SDK

Production-ready SDK for Roblox executors with advanced anti-bypass protection.

## Quick Start

### Method 1: Quick Loader (Recommended)
\`\`\`lua
loadstring(game:HttpGet("https://cloudipv.myartsonline.com/p/loader.lua"))()
VexNetExecute("YOUR-KEY-HERE")
\`\`\`

### Method 2: Direct SDK Usage
\`\`\`lua
local SDK = loadstring(game:HttpGet("https://cloudipv.myartsonline.com/p/sdk.lua"))()
local vexnet = SDK.new("https://vapp-vexnetnetworkofficial.wasmer.app/")

-- Sync
local sync = vexnet:sync()

-- Validate key
local valid = vexnet:checkKey("YOUR-KEY")

-- Execute script
if valid then
    vexnet:executeScript("script-id", "YOUR-KEY")
end
\`\`\`

## Executor Compatibility

Tested and working on:
- Synapse X / Synapse Z
- Script-Ware
- KRNL
- Fluxus
- Electron
- Trigon
- Arceus X
- Delta
- Codex

## API Reference

### VexNetSDK.new(proxy_url, fingerprint)
Create new SDK instance.

### vexnet:sync()
Sync with server and get node list.

### vexnet:checkKey(key)
Validate key and perform hidden verification.

### vexnet:loadScript(script_id, key)
Load script code from server.

### vexnet:executeScript(script_id, key)
Validate key and execute script in one call.

## Security Features

- AES-256-CBC encryption
- SHA256 signature verification
- Hidden verification layer
- Anti-hook detection
- Server token validation
- Nonce-based replay prevention
- Timestamp validation (3s tolerance)

## File Structure

\`\`\`
cloudipv.myartsonline.com/p/
├── sdk.lua          # Main SDK (load via loadstring)
├── loader.lua       # Quick loader with auto-setup
├── example.lua      # Usage examples
└── README.md        # This file
\`\`\`

## Support

For issues or questions, contact VexNet support.
