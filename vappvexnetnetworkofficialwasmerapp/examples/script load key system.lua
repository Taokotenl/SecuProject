-- Advanced Key System Example with Auto-Check and Storage  
-- This example saves validated keys and auto-validates on script start  
  
local SDK = loadstring(game:HttpGet("https://vapp-vexnetnetworkofficial.wasmer.app/sdk"))()  
local KeySystem = SDK.new("https://vapp-vexnetnetworkofficial.wasmer.app")  
  
-- Services  
local Players = game:GetService("Players")  
local HttpService = game:GetService("HttpService")  
  
local player = Players.LocalPlayer  
local keyStorageFile = "key_storage_" .. player.UserId .. ".txt"  
  
-- Check if key is saved  
local function getSavedKey()  
    if isfile and readfile then  
        if isfile(keyStorageFile) then  
            return readfile(keyStorageFile)  
        end  
    end  
    return nil  
end  
  
-- Save key to file  
local function saveKey(key)  
    if writefile then  
        writefile(keyStorageFile, key)  
        print("[Key System] Key saved for future sessions")  
    end  
end  
  
-- Delete saved key  
local function deleteSavedKey()  
    if delfile and isfile then  
        if isfile(keyStorageFile) then  
            delfile(keyStorageFile)  
            print("[Key System] Saved key deleted")  
        end  
    end  
end  
  
-- Main script logic  
local function runMainScript()  
    print("=================================")  
    print("[Main Script] Access Granted!")  
    print("[Main Script] Loading features...")  
    print("=================================")  
      
    -- Your main script code here  
    -- Example:  
    game.StarterGui:SetCore("SendNotification", {  
        Title = "Script Loaded";  
        Text = "All features are now active!";  
        Duration = 5;  
    })  
      
    -- Add your script features here  
    print("[Main Script] All systems operational!")  
end  
  
-- Check key validity  
local function validateKey(key, showUI)  
    print("[Key System] Validating key...")  
      
    local success, err = KeySystem:checkKey(key)  
      
    if success then  
        print("[Key System] ✓ Key is valid!")  
        saveKey(key)  
        runMainScript()  
        return true  
    else  
        print("[Key System] ✗ Key validation failed:", err)  
        if showUI then  
            deleteSavedKey()  
        end  
        return false  
    end  
end  
  
-- Main initialization  
print("[Key System] Initializing...")  
  
-- Try to use saved key first  
local savedKey = getSavedKey()  
if savedKey then  
    print("[Key System] Found saved key, validating...")  
    if validateKey(savedKey, false) then  
        return -- Success, script is running  
    else  
        print("[Key System] Saved key is invalid, showing UI...")  
    end  
end  
  
-- Fixed URL to load UI from proxy instead of GitHub  
-- No valid saved key, show UI  
print("[Key System] Loading authentication UI...")  
loadstring(game:HttpGet("https://vapp-vexnetnetworkofficial.wasmer.app/ui"))()  
