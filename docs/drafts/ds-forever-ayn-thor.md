**Turn your AYN Thor into a dual-screen World of Warcraft handheld.**

This guide walks you through installing DS Forever on the AYN Thor — from downloading your first APK to entering Azeroth with a controller.

No previous Android modding, Linux, emulation, or PC gaming experience is required. For experienced users, the guide also includes technical details, alternative setup paths, and troubleshooting.

DS Forever is an unofficial, community-developed companion for World of Warcraft: Forever. It can run the game locally on your Thor or stream it from a Windows PC. The game appears on the upper display, while the lower touchscreen provides access to maps, quests, inventory, abilities, and other game functions. 

## 1. Before you begin

You'll need the following:

| Requirement      | Details                                                                    |
| ---------------- | -------------------------------------------------------------------------- |
| Device           | AYN Thor                                                                   |
| Operating system | Android 10 or newer, 64-bit ARM                                            |
| Internet         | A reliable Wi-Fi connection                                                |
| Storage          | Approximately 60 GB free recommended for local installation                |
| Game access      | An authorized World of Warcraft: Forever account with beta access          |
| Game files       | Either download them through DS Forever or select an existing installation |
| Optional         | A Windows PC for streaming instead of local gameplay                       |

DS Forever's direct installation starts with approximately 250 MB, but the game downloads additional data as needed and can eventually use roughly 50 GB. Reserve 60 GB so you have room for downloads and updates. 

**Official downloads**

[**Latest DS Forever release**](https://github.com/Jreese702/DS-Forever/releases/latest)

Android APK and optional Windows Desktop files

v1.0.32 verified

[**Source code and project documentation**](https://github.com/Jreese702/DS-Forever)

GitHub repository maintained by Jreese702

The latest release I verified is **DS Forever 1.0.32**, published November 14, 2026. The guide should direct people to the latest release link rather than a hard-coded download that might become outdated. 

## 2. Download and install DS Forever

> **Screenshot needed:** GitHub release showing the APK under Assets

1. Turn on your AYN Thor and connect it to Wi-Fi.
2. Open a web browser on the Thor.
3. Visit the [official DS Forever releases page](https://github.com/Jreese702/DS-Forever/releases/latest).
4. Find the **Assets** section. If it is collapsed, tap to expand it.
5. Download the file named `DS-Forever-<version>.apk` (for example, `DS-Forever-1.0.32.apk`).
6. Open the downloaded file, using the browser's download notification or Android's Files app.
7. If Android asks whether to allow your browser or file manager to install unknown apps, allow it for that specific source.
8. Select **Install**, then **Open**.

**What is an APK?** An Android Package Kit is an installation file for Android apps. Think of it as the Android equivalent of a Windows `.exe` installer.

Advanced note: Download signed releases from the official GitHub repository. Do not install unverified APKs from third-party mirrors. If supplied, the SHA256SUMS file can be used for additional download-integrity verification.

## 3. Choose how you want to play

DS Forever offers two ways to run World of Warcraft.

![Ayn Thor review: the best retro console. Full stop - Galaxus](https://images.openai.com/static-rsc-4/S_mRqFIHWUWpVjATVUK30ugT1ULdoh6SI016xxt11dNp-ca9Mf9v_JJhYg8KeD8stUweZcBvyxdnVIIeWvBu9Y2Qt5mRO74SQ4nSvgxmNg9MoqAkQqtLnEV_ukLYIsHx3De2IFpHataylRPXAdxXdZxVtLuS2VB83e9_WvItNng?purpose=inline)

**Option A — Play directly on the Thor**

The game runs on the handheld itself. You won't need a gaming PC, although downloading game files requires an internet connection.

Recommended for users who want a self-contained handheld experience.

![Acer Predator Orion 5000 (2024) review: Killer value | Tom's Guide](https://images.openai.com/static-rsc-4/ldmxJDwD8llizjeBLPdWR4qI5ujFcKQT99-4Cx0GlBT_CwDh_uynpoJMRn_v9n4QWasVg0srb75qkFN8jwmvsRa9Yn48Nvm7liY2Db1ZuZGFLlyzoneqFukk5VDOnfIHg4PzBGAnJzC-DPINB9wfnbcZoogea-FpLeIfGOwiNsA?purpose=inline)

**Option B — Stream from your Windows PC**

Your PC runs the game and sends the video to the Thor. DS Forever still provides its companion interface on the lower screen.

Useful for playing through your existing PC or using its processing power.

We'll cover local installation first. PC streaming is explained separately, so you can skip directly to it if that is how you intend to play.

## 4. Set up the game on your Thor

These instructions follow the project's first-run setup flow. Button labels are taken from the official installation guide; some screens may differ slightly in newer releases.

### 4.1 Open DS Forever

Launch the app from your Android app list.

You should now see the setup interface across both screens.

- **Upper screen:** Environment Readiness — a status checklist showing what's ready and what still needs attention.
- **Lower screen:** The setup wizard, which guides you through each required action.

You can use the touchscreen or the D-pad to move the gold selection cursor and press **A** to select an item.

> **Screenshot needed:** Initial setup, showing both displays

### 4.2 Install World of Warcraft

On the lower screen, find the question **Where is WoW Forever?**

Choose your installation method

Install directly — new installation without a PC

Choose folder — use existing game files

Press **Install directly**.

DS Forever will download the initial files, prepare the game environment and build the graphics required for the companion interface.

The game continues downloading additional content as you play. Keep the Thor connected to Wi-Fi and make sure there is adequate free storage.

> **Screenshot needed:** Lower screen with “Where is WoW Forever?” and “Install directly” highlighted.

**For advanced users:** The required existing game folder structure is:

```
WoW Forever/
├── .build.info
└── Data/
    └── ...
```

The `.build.info` file is hidden on some operating systems. The Windows executable is not required for this method; DS Forever downloads the appropriate ARM64 game client separately. 

### 4.3 Grant file access

Android may ask DS Forever for permission to access your game files.

1. Select **Open settings** on the lower screen.
2. Find **Allow access to manage all files**.
3. Enable the permission for DS Forever.
4. Return to the app.

This permission lets DS Forever read the game installation, create its companion resources and manage required files.

> **Screenshot needed:** Android “All files access” permission enabled for DS Forever

### 4.4 Let DS Forever prepare the game

The app now verifies your installation and prepares resources from your game files.

**Game folder:** Found and accessible

**Add-ons:** Installed or being prepared

**Game art:** Progress and current stage

**Indoor maps and additional icons:** Background processing

The seven required art stages cover the interface, controller graphics, icons, world maps, map symbols, NPC icons and minimap. All seven must complete before the game is ready to launch.

Some additional work, such as indoor maps and the complete icon catalogue, continues separately and does not prevent you from playing. If a required step fails, DS Forever provides a reason and a **Try again** option. 

> **Screenshot needed:** Environment Readiness during art generation, then again after all required checks pass

### 4.5 Configure your Battle.net login

While resources are being prepared, DS Forever may prompt you to save your Battle.net credentials.

1. Select **Enter login**.
2. Enter the email address associated with your Battle.net account.
3. Enter your password.
4. Choose **Save and log in**.

DS Forever stores the credentials encrypted using Android's secure Keystore. For local gameplay, you don't have to enter them every time. If you prefer not to save credentials, select **Play without** and enter them manually in-game.

If Battle.net asks for authentication or a CAPTCHA, complete that step manually. Avoid repeated failed sign-in attempts, as these can trigger additional security challenges.

> **Screenshot needed:** Battle.net login prompt, with all email addresses, passwords and account identifiers hidden

### 4.6 Launch the game

Once the required checks pass, choose **Local**.

On the first launch, DS Forever prepares its Windows-compatible game environment. The game then starts on the upper display.

Choose your character, enter the world and check that the companion interface appears on the lower display.

> **Screenshot needed:** Both screens showing a successfully loaded character in-game

## 5. Learn the controls

Once you are in the game, you have two interfaces working together. The upper screen displays World of Warcraft, while the lower screen shows DS Forever's companion controls.

![I played WoW on my Asus ROG Ally so you don’t have to — here’s what happened | Laptop Mag](https://images.openai.com/static-rsc-4/pg85b8ne7Yt7fg1ykNw75_6-syIa_fCwPBQHUFjWV-UxXtG2knVx-kVJk_A4lMsl2kAaBJSXrCrdxqGe_e6WEzRDRjKg4TsS8u49NFVvWYYfj9LJv_DtjwgsSQRgPMR9zoGFLsVc2iDbQoTMwxiPfqrXRo3u8nJYnDHvpFnsbfQ?purpose=inline)

**The Home screen**

The main companion view gives you access to the map, focused quest, chat and quick actions. Other rooms are accessible through the lower navigation bar.

You can navigate using the touchscreen or physical controls. The D-pad moves the selection cursor through the lower-screen interface, while **A** selects items. The app also provides controller-binding options for combat actions, abilities and other functions.

Once you're comfortable moving around, open the **Controller** room to configure your abilities. The **Spellbook** room provides another way to select spells and bind them to controls.

I recommend keeping the default bindings initially, playing for a few minutes and then adjusting them one at a time.

For the full controller reference, see the project's [Controller and Combat guide](https://github.com/Jreese702/DS-Forever/wiki/Controller-and-Combat).

## 6. Optional: Stream from a Windows PC

This section is for users who want the game to run on a Windows computer while the AYN Thor displays the gameplay and companion interface.

### Set up the Windows PC

1. On your Windows computer, open the [DS Forever releases page](https://github.com/Jreese702/DS-Forever/releases/latest).
2. Download `DsForeverDesktop-<version>-win-x64.zip` from the release.
3. Extract the ZIP archive to a permanent folder, such as `C:\Games\DSForeverDesktop`.
4. Run `DsForeverDesktop.exe`.
5. Follow any first-run prompts. Windows may warn that the application is unsigned; verify that it came from the official project before proceeding.
6. Leave DS Forever Desktop running.

### Connect your Thor

1. Connect the Thor and the Windows PC to the same home network.
2. Open DS Forever on the Thor.
3. Choose **Streaming**.
4. Enter the pairing code displayed by DS Forever Desktop.
5. Complete the connection.

DS Forever Desktop starts the game on the PC and uses the Thor for the dual-screen experience.

The Windows Desktop component and Android companion should be on compatible releases because their game add-ons need to match. Current Desktop releases can update themselves, but older versions may require a manual replacement. 

