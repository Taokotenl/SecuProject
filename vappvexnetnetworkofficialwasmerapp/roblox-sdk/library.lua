-- Public API Library for scripts
-- Lightweight wrapper for SDK functionality

local Library = {}
Library.__index = Library

local Players = game:GetService("Players")

function Library.new(sdk_instance)
    local self = setmetatable({}, Library)
    self.sdk = sdk_instance
    self.is_authenticated = false
    self.current_key = nil
    return self
end

-- Added kick player function for unauthorized access
function Library:_kickPlayer(reason)
    local player = Players.LocalPlayer
    if player then
        player:Kick("[VexNet Security] " .. reason)
    end
end

function Library:authenticate(key)
    local valid, err = self.sdk:checkKey(key)
    
    if not valid then
        warn("Authentication failed: " .. (err or "Invalid key"))
        
        -- Kick player if unauthorized or bypass detected
        if err == "Unauthorized" then
            self:_kickPlayer("Unauthorized access detected. SDK integrity compromised.")
            return false
        end
        
        return false
    end
    
    -- Verify SDK was properly validated
    if not self.sdk._verified then
        self:_kickPlayer("SDK verification failed. Possible bypass attempt.")
        return false
    end
    
    self.is_authenticated = true
    self.current_key = key
    return true
end

function Library:execute(script_id)
    if not self.is_authenticated then
        error("Not authenticated. Call authenticate(key) first.")
    end
    
    local script_code, err = self.sdk:loadScript(script_id, self.current_key)
    
    if not script_code then
        error("Failed to load script: " .. (err or "Unknown error"))
    end
    
    local env = {
        game = game,
        script = script,
        print = print,
        warn = warn,
        task = task,
        wait = wait,
        Instance = Instance,
        pairs = pairs,
        ipairs = ipairs,
        table = table,
        string = string,
        math = math,
        debug = debug
    }
    
    local func = loadstring(script_code)
    setfenv(func, env)
    
    return func()
end

return Library
