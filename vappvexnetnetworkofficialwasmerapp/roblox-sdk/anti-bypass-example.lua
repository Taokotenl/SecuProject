-- Example: How to use the Anti-Bypass SDK in your Roblox script
-- This demonstrates the enhanced security features

local SDK = require(script.Parent:WaitForChild("sdk"))
local Library = require(script.Parent:WaitForChild("library"))

-- Configuration
local PROXY_URL = "https://vapp-vexnetnetworkofficial.wasmer.app/proxy.php"
local SCRIPT_ID = "main-script"

print("=== VexNet Secure Key System ===")
print("Initializing SDK with anti-bypass protection...")

-- Initialize SDK with unique fingerprint
local sdk = SDK.new(PROXY_URL, game:GetService("RbxAnalyticsService"):GetClientId())

-- Step 1: Sync with server (includes hook detection)
print("\n[1/3] Syncing with server...")
local sync_result, sync_err = sdk:sync()

if not sync_result then
    warn("Sync failed: " .. tostring(sync_err))
    if sync_err == "Unauthorized" then
        error("⚠️ SECURITY ALERT: Bypass attempt detected! SDK hooks found.")
    end
    error("Failed to connect to server")
end

print("✓ Connected to " .. #sync_result.nodes .. " secure nodes")

-- Step 2: Initialize library
local lib = Library.new(sdk)

-- Step 3: Get key from user (replace with your key input method)
print("\n[2/3] Authenticating key...")
local user_key = "XXXX-XXXX-XXXX" -- Replace with actual key input

-- Authenticate (includes hidden verification step)
local auth_success = lib:authenticate(user_key)

if not auth_success then
    warn("Authentication failed!")
    error("Invalid key or bypass detected")
end

print("✓ Key authenticated successfully")
print("✓ SDK verification passed")

-- Step 4: Execute protected script
print("\n[3/3] Loading protected script...")
local success, result = pcall(function()
    return lib:execute(SCRIPT_ID)
end)

if success then
    print("✓ Script executed successfully!")
    print("\n=== Security Features Active ===")
    print("• Hook detection: ENABLED")
    print("• Hidden verification: PASSED")
    print("• Server signature: VALIDATED")
    print("• Anti-bypass: ACTIVE")
else
    warn("Script execution failed: " .. tostring(result))
end
