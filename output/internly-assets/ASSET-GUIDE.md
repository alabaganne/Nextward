# Internly character collection

Six coordinated soft 3D illustrations and two silent animated loops. The website was not changed. The recurring characters are an olive-skinned student with dark curly hair and a blue sweatshirt, and a recruiter with a dark low bun and navy blazer.

## Preview

Open `index.html` in a browser. The white, pale blue, navy, and transparency controls let you check the artwork against different backgrounds. Videos play only when you press play. The gallery also works through a local static server.

## Illustrations

All six files are transparent RGBA PNGs. Portraits retain their native generated resolution of **1254 × 1254**, above the 1024px target. Scenes are **1536 × 1024**. Use `object-fit: contain` to keep the full composition; portraits also work within circular avatar containers.

| File | Suggested placement |
|---|---|
| `stills/student-avatar.png` | Student account choice, onboarding, student feature cards |
| `stills/recruiter-avatar.png` | Company account choice, company feature cards, hiring guidance |
| `stills/conversation.png` | Hero, messaging features, connection or support sections |
| `stills/student-workspace.png` | Internship discovery, applications, student benefits |
| `stills/company-workspace.png` | Hiring workflow, application review, company benefits |
| `stills/shared-success.png` | Closing call to action, onboarding completion, success states |

The characters illustrate user roles. Text and branding remain separate so the assets can be reused at different sizes and with different copy.

## Motion

Both loops are **1200 × 800**, **6 seconds**, **30 fps**, **180 frames**, and have no audio track. Character artwork stays still while separate overlays move.

| File | Format / background | Motion |
|---|---|---|
| `loops/conversation.webm` | VP9 WebM with alpha | Two chat bubbles, staggered typing dots, soft reset |
| `loops/conversation.mp4` | H.264 MP4 on `#F6F8FC` | Same conversation loop |
| `loops/conversation-poster.png` | Transparent PNG | Conversation at 2.7 seconds |
| `loops/success.webm` | VP9 WebM with alpha | Drawn checkmark, twelve blue confetti pieces, soft reset |
| `loops/success.mp4` | H.264 MP4 on `#F6F8FC` | Same success loop |
| `loops/success-poster.png` | Transparent PNG | Celebration at 1.8 seconds |

Choose WebM where transparent video is supported; choose MP4 when a pale background fits the layout. Keep the PNG poster available as a still alternative. When integrating later, use `muted`, `playsinline`, and `loop`; show the poster instead of autoplaying for visitors who prefer reduced motion.

## Editable sources and generation prompts

- `motion/conversation/index.html` and `motion/success/index.html`: editable HyperFrames compositions, with local copies of their artwork and GSAP runtime.
- `motion/render.mjs`: rerenders the WebM loops, produces pale-background MP4 alternatives, and extracts transparent posters.
- `prompts.json`: the complete six generation prompts and the character-reference relationships.
- `DESIGN.md`: palette, materials, lighting, and motion direction.
- `validation.json`: dimensions, alpha checks, video properties, file sizes, and measured loop continuity.
- `previews/`: light/dark gallery screenshots and first/middle/final frame contact sheets.

Images were created separately with the **built-in image generator**, first establishing the student and recruiter portraits, then using those portraits as character references. No image-generation API key or fallback CLI was used. Motion was authored and rendered with **HyperFrames 0.8.150 and GSAP 3.14.2**. The success particles adapt the HyperFrames registry's deterministic confetti pattern. FFmpeg encodes the MP4 alternatives and extracts poster frames.

### Rerender on Linux x64

Node.js 22+ is required. From this asset folder:

```sh
npm install --prefix motion/tools
node motion/render.mjs
```

The included tooling manifest provides local FFmpeg and FFprobe binaries; `render.mjs` makes them executable before using them. On other platforms, use system FFmpeg and FFprobe instead of the Linux packages. No dependencies need to be added to the application itself.

To regenerate MP4s and posters from existing WebM files:

```sh
node motion/render.mjs --encode-only
```

For the editable conversation preview, run this from `motion/conversation`:

```sh
npx --yes hyperframes@0.8.150 preview --background
```

The command prints the Studio URL. The HyperFrames desktop app is another option for editing the composition through a visual editor and chat: https://hyperframes.dev/studio/download?os=linux.

## Verification

- All six illustrations have genuine alpha transparency and were visually reviewed on white and navy backgrounds.
- Hands, faces, clothing, and character consistency were inspected. No text or branding is baked into the illustrations.
- Both HyperFrames projects passed runtime, layout, and motion checks, with zero errors. Two structural suggestions per project were reviewed: the simple single-scene compositions intentionally keep their layers together instead of introducing sub-compositions.
- WebM alpha was confirmed in both file metadata and decoded pixels. MP4 backgrounds were checked against the intended pale color.
- All four video exports contain exactly 180 frames, run for six seconds, and contain no audio.
- First and final decoded WebM frames differ by less than 0.04 intensity levels per color channel on average out of 255, consistent with a seamless visual join after lossy encoding.
