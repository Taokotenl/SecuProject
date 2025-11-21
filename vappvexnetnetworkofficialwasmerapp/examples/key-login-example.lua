-- Key Login Example Script for Roblox Executors
-- This script demonstrates how to use the key system with a beautiful UI

local SDK = loadstring(game:HttpGet("https://vapp-vexnetnetworkofficial.wasmer.app/sdk"))()
local KeySystem = SDK.new("https://vapp-vexnetnetworkofficial.wasmer.app")

-- Services
local Players = game:GetService("Players")
local TweenService = game:GetService("TweenService")
local UserInputService = game:GetService("UserInputService")
local HttpService = game:GetService("HttpService")

-- Create unique fingerprint for this device
local player = Players.LocalPlayer
local fingerprint = HttpService:GenerateGUID(false)

-- Initialize SDK with fingerprint
KeySystem.fingerprint = fingerprint

-- UI Creation
local function createUI()
    -- Create ScreenGui
    local screenGui = Instance.new("ScreenGui")
    screenGui.Name = "KeySystemUI"
    screenGui.ResetOnSpawn = false
    screenGui.ZIndexBehavior = Enum.ZIndexBehavior.Sibling
    screenGui.Parent = game.CoreGui
    
    -- Main Frame (Background blur)
    local mainFrame = Instance.new("Frame")
    mainFrame.Name = "MainFrame"
    mainFrame.Size = UDim2.new(0, 450, 0, 300)
    mainFrame.Position = UDim2.new(0.5, -225, 0.5, -150)
    mainFrame.BackgroundColor3 = Color3.fromRGB(20, 20, 25)
    mainFrame.BorderSizePixel = 0
    mainFrame.Parent = screenGui
    
    -- Add corner radius
    local corner = Instance.new("UICorner")
    corner.CornerRadius = UDim.new(0, 12)
    corner.Parent = mainFrame
    
    -- Add shadow/glow effect
    local shadow = Instance.new("ImageLabel")
    shadow.Name = "Shadow"
    shadow.BackgroundTransparency = 1
    shadow.Position = UDim2.new(0, -15, 0, -15)
    shadow.Size = UDim2.new(1, 30, 1, 30)
    shadow.Image = "rbxasset://textures/ui/GuiImagePlaceholder.png"
    shadow.ImageColor3 = Color3.fromRGB(100, 150, 255)
    shadow.ImageTransparency = 0.7
    shadow.ScaleType = Enum.ScaleType.Slice
    shadow.SliceCenter = Rect.new(10, 10, 10, 10)
    shadow.Parent = mainFrame
    
    -- Title
    local title = Instance.new("TextLabel")
    title.Name = "Title"
    title.Size = UDim2.new(1, 0, 0, 60)
    title.Position = UDim2.new(0, 0, 0, 0)
    title.BackgroundTransparency = 1
    title.Text = "🔐 Key Authentication"
    title.TextColor3 = Color3.fromRGB(255, 255, 255)
    title.TextSize = 24
    title.Font = Enum.Font.GothamBold
    title.Parent = mainFrame
    
    -- Subtitle
    local subtitle = Instance.new("TextLabel")
    subtitle.Name = "Subtitle"
    subtitle.Size = UDim2.new(1, -40, 0, 30)
    subtitle.Position = UDim2.new(0, 20, 0, 60)
    subtitle.BackgroundTransparency = 1
    subtitle.Text = "Enter your key to continue"
    subtitle.TextColor3 = Color3.fromRGB(180, 180, 190)
    subtitle.TextSize = 14
    subtitle.Font = Enum.Font.Gotham
    subtitle.TextXAlignment = Enum.TextXAlignment.Left
    subtitle.Parent = mainFrame
    
    -- Key Input Container
    local inputContainer = Instance.new("Frame")
    inputContainer.Name = "InputContainer"
    inputContainer.Size = UDim2.new(1, -40, 0, 50)
    inputContainer.Position = UDim2.new(0, 20, 0, 105)
    inputContainer.BackgroundColor3 = Color3.fromRGB(30, 30, 38)
    inputContainer.BorderSizePixel = 0
    inputContainer.Parent = mainFrame
    
    local inputCorner = Instance.new("UICorner")
    inputCorner.CornerRadius = UDim.new(0, 8)
    inputCorner.Parent = inputContainer
    
    -- Key Input TextBox
    local keyInput = Instance.new("TextBox")
    keyInput.Name = "KeyInput"
    keyInput.Size = UDim2.new(1, -20, 1, -10)
    keyInput.Position = UDim2.new(0, 10, 0, 5)
    keyInput.BackgroundTransparency = 1
    keyInput.Text = ""
    keyInput.PlaceholderText = "Paste your key here..."
    keyInput.PlaceholderColor3 = Color3.fromRGB(120, 120, 130)
    keyInput.TextColor3 = Color3.fromRGB(255, 255, 255)
    keyInput.TextSize = 14
    keyInput.Font = Enum.Font.GothamMedium
    keyInput.TextXAlignment = Enum.TextXAlignment.Left
    keyInput.ClearTextOnFocus = false
    keyInput.Parent = inputContainer
    
    -- Submit Button
    local submitButton = Instance.new("TextButton")
    submitButton.Name = "SubmitButton"
    submitButton.Size = UDim2.new(1, -40, 0, 45)
    submitButton.Position = UDim2.new(0, 20, 0, 175)
    submitButton.BackgroundColor3 = Color3.fromRGB(100, 150, 255)
    submitButton.BorderSizePixel = 0
    submitButton.Text = "Verify Key"
    submitButton.TextColor3 = Color3.fromRGB(255, 255, 255)
    submitButton.TextSize = 16
    submitButton.Font = Enum.Font.GothamBold
    submitButton.AutoButtonColor = false
    submitButton.Parent = mainFrame
    
    local buttonCorner = Instance.new("UICorner")
    buttonCorner.CornerRadius = UDim.new(0, 8)
    buttonCorner.Parent = submitButton
    
    -- Get Key Button
    local getKeyButton = Instance.new("TextButton")
    getKeyButton.Name = "GetKeyButton"
    getKeyButton.Size = UDim2.new(1, -40, 0, 35)
    getKeyButton.Position = UDim2.new(0, 20, 0, 235)
    getKeyButton.BackgroundColor3 = Color3.fromRGB(40, 40, 50)
    getKeyButton.BorderSizePixel = 0
    getKeyButton.Text = "Get Key"
    getKeyButton.TextColor3 = Color3.fromRGB(200, 200, 210)
    getKeyButton.TextSize = 14
    getKeyButton.Font = Enum.Font.Gotham
    getKeyButton.AutoButtonColor = false
    getKeyButton.Parent = mainFrame
    
    local getKeyCorner = Instance.new("UICorner")
    getKeyCorner.CornerRadius = UDim.new(0, 8)
    getKeyCorner.Parent = getKeyButton
    
    -- Status Label
    local statusLabel = Instance.new("TextLabel")
    statusLabel.Name = "StatusLabel"
    statusLabel.Size = UDim2.new(1, -40, 0, 20)
    statusLabel.Position = UDim2.new(0, 20, 0, 280)
    statusLabel.BackgroundTransparency = 1
    statusLabel.Text = ""
    statusLabel.TextColor3 = Color3.fromRGB(255, 100, 100)
    statusLabel.TextSize = 12
    statusLabel.Font = Enum.Font.Gotham
    statusLabel.TextXAlignment = Enum.TextXAlignment.Center
    statusLabel.Visible = false
    statusLabel.Parent = mainFrame
    
    -- Loading Indicator
    local loadingFrame = Instance.new("Frame")
    loadingFrame.Name = "LoadingFrame"
    loadingFrame.Size = UDim2.new(1, 0, 1, 0)
    loadingFrame.Position = UDim2.new(0, 0, 0, 0)
    loadingFrame.BackgroundColor3 = Color3.fromRGB(20, 20, 25)
    loadingFrame.BackgroundTransparency = 0.3
    loadingFrame.BorderSizePixel = 0
    loadingFrame.Visible = false
    loadingFrame.Parent = mainFrame
    
    local loadingCorner = Instance.new("UICorner")
    loadingCorner.CornerRadius = UDim.new(0, 12)
    loadingCorner.Parent = loadingFrame
    
    local loadingText = Instance.new("TextLabel")
    loadingText.Size = UDim2.new(1, 0, 1, 0)
    loadingText.BackgroundTransparency = 1
    loadingText.Text = "Verifying..."
    loadingText.TextColor3 = Color3.fromRGB(255, 255, 255)
    loadingText.TextSize = 18
    loadingText.Font = Enum.Font.GothamBold
    loadingText.Parent = loadingFrame
    
    return {
        screenGui = screenGui,
        mainFrame = mainFrame,
        keyInput = keyInput,
        submitButton = submitButton,
        getKeyButton = getKeyButton,
        statusLabel = statusLabel,
        loadingFrame = loadingFrame,
        loadingText = loadingText
    }
end

-- Animation helpers
local function tweenButton(button, hovering)
    local goal = {}
    if hovering then
        goal.BackgroundColor3 = Color3.fromRGB(120, 170, 255)
        goal.Size = UDim2.new(button.Size.X.Scale, button.Size.X.Offset, 0, 48)
    else
        goal.BackgroundColor3 = Color3.fromRGB(100, 150, 255)
        goal.Size = UDim2.new(button.Size.X.Scale, button.Size.X.Offset, 0, 45)
    end
    
    local tween = TweenService:Create(button, TweenInfo.new(0.2), goal)
    tween:Play()
end

local function showStatus(ui, message, isError)
    local statusLabel = ui.statusLabel
    statusLabel.Text = message
    statusLabel.TextColor3 = isError and Color3.fromRGB(255, 100, 100) or Color3.fromRGB(100, 255, 150)
    statusLabel.Visible = true
    
    task.delay(3, function()
        statusLabel.Visible = false
    end)
end

-- Main logic
local function checkKey(ui, key)
    -- Show loading
    ui.loadingFrame.Visible = true
    ui.submitButton.Active = false
    
    -- Validate key
    local success, err = KeySystem:checkKey(key)
    
    -- Hide loading
    ui.loadingFrame.Visible = false
    ui.submitButton.Active = true
    
    if success then
        showStatus(ui, "✓ Key verified successfully!", false)
        
        -- Animate out
        local tween = TweenService:Create(ui.mainFrame, TweenInfo.new(0.5), {
            Position = UDim2.new(0.5, -225, -0.5, -150)
        })
        tween:Play()
        
        task.wait(0.5)
        ui.screenGui:Destroy()
        
        -- Load your main script here
        print("[Key System] Access granted! Loading main script...")
        
        -- Example: Load your main script
        -- loadstring(game:HttpGet("your-script-url"))()
        
        return true
    else
        showStatus(ui, "✗ " .. (err or "Invalid key"), true)
        
        -- Shake animation
        local originalPos = ui.mainFrame.Position
        for i = 1, 3 do
            ui.mainFrame.Position = UDim2.new(0.5, -235, 0.5, -150)
            task.wait(0.05)
            ui.mainFrame.Position = UDim2.new(0.5, -215, 0.5, -150)
            task.wait(0.05)
        end
        ui.mainFrame.Position = originalPos
        
        return false
    end
end

-- Initialize UI
local ui = createUI()

-- Button hover effects
ui.submitButton.MouseEnter:Connect(function()
    tweenButton(ui.submitButton, true)
end)

ui.submitButton.MouseLeave:Connect(function()
    tweenButton(ui.submitButton, false)
end)

-- Submit button click
ui.submitButton.MouseButton1Click:Connect(function()
    local key = ui.keyInput.Text
    if key == "" then
        showStatus(ui, "Please enter a key", true)
        return
    end
    
    checkKey(ui, key)
end)

-- Get Key button click
ui.getKeyButton.MouseButton1Click:Connect(function()
    local linkvertiseUrl = "https://linkvertise.com/123456/get-key"
    setclipboard(linkvertiseUrl)
    showStatus(ui, "Link copied to clipboard!", false)
    
    -- Open in browser if supported
    if syn and syn.request then
        syn.request({
            Url = linkvertiseUrl,
            Method = "GET"
        })
    end
end)

-- Enter key to submit
ui.keyInput.FocusLost:Connect(function(enterPressed)
    if enterPressed then
        ui.submitButton.MouseButton1Click:Fire()
    end
end)

-- Entrance animation
ui.mainFrame.Position = UDim2.new(0.5, -225, -0.5, -150)
local entranceTween = TweenService:Create(ui.mainFrame, TweenInfo.new(0.5, Enum.EasingStyle.Back, Enum.EasingDirection.Out), {
    Position = UDim2.new(0.5, -225, 0.5, -150)
})
entranceTween:Play()

print("[Key System] UI loaded. Please enter your key to continue.")
