-- VexNet Usage Example
-- This demonstrates how to use the VexNet SDK in your scripts

-- Method 1: Load SDK directly
local VexNetSDK = loadstring(game:HttpGet("https://cloudipv.myartsonline.com/p/sdk.lua"))()
local vexnet = VexNetSDK.new("https://vapp-vexnetnetworkofficial.wasmer.app/")

-- Sync with server
local sync_result, sync_err = vexnet:sync()
if not sync_result then
    error("Failed to sync: " .. tostring(sync_err))
end

print("Connected to " .. #sync_result.nodes .. " nodes")

-- Check key validity
local key = "XXXX-XXXX-XXXX-XXXX" -- Replace with actual key
local valid, response = vexnet:checkKey(key)

if valid then
    print("Key is valid!")
    
    -- Load and execute script
    local success, result = vexnet:executeScript("main-script", key)
    
    if success then
        print("Script executed successfully!")
    else
        warn("Execution failed: " .. tostring(result))
    end
else
    warn("Invalid key: " .. tostring(response))
end

-- Method 2: Use quick loader (recommended)
loadstring(game:HttpGet("https://cloudipv.myartsonline.com/p/loader.lua"))()

-- Then simply call:
VexNetExecute("YOUR-KEY-HERE", "main-script")

-- Method 3: Manual key check and script load
local VexNet = getgenv().VexNet

if VexNet then
    local is_valid, check_data = VexNet:checkKey("YOUR-KEY-HERE")
    
    if is_valid then
        local script_code, err = VexNet:loadScript("main-script", "YOUR-KEY-HERE")
        
        if script_code then
            loadstring(script_code)()
        else
            warn("Failed to load: " .. tostring(err))
        end
    end
end
