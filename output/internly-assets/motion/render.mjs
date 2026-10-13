import { spawnSync } from 'node:child_process';
import { existsSync, chmodSync } from 'node:fs';
import { dirname, join, delimiter } from 'node:path';
import { fileURLToPath } from 'node:url';

const motionDir = dirname(fileURLToPath(import.meta.url));
const assetDir = dirname(motionDir);
const toolDirs = [
  join(motionDir, 'tools/node_modules/@ffmpeg-installer/linux-x64'),
  join(motionDir, 'tools/node_modules/@ffprobe-installer/linux-x64'),
];
for (const [index, name] of ['ffmpeg', 'ffprobe'].entries()) {
  const binary = join(toolDirs[index], name);
  if (existsSync(binary)) chmodSync(binary, 0o755);
}
const env = { ...process.env, PATH: [...toolDirs, process.env.PATH].join(delimiter) };
function run(command, args, cwd = motionDir) {
  const result = spawnSync(command, args, { cwd, env, stdio: 'inherit' });
  if (result.error) throw result.error;
  if (result.status !== 0) throw new Error(`${command} exited with ${result.status}`);
}
for (const scene of ['conversation', 'success']) {
  const projectDir = join(motionDir, scene);
  const webm = join(assetDir, 'loops', `${scene}.webm`);
  if (!process.argv.includes('--encode-only')) {
    run('npm', ['run', 'check'], projectDir);
    run('npx', ['--yes', 'hyperframes@0.8.150', 'render', '--format', 'webm', '--fps', '30', '--quality', 'high', '--workers', '2', '--output', webm], projectDir);
  }
  // libvpx preserves the WebM alpha plane; the native decoder may discard it.
  run('ffmpeg', ['-hide_banner', '-loglevel', 'error', '-y', '-c:v', 'libvpx-vp9', '-i', webm,
    '-filter_complex', 'color=c=0xF6F8FC:s=1200x800:r=30:d=6[bg];[bg][0:v]overlay=shortest=1:format=auto,format=yuv420p[out]',
    '-map', '[out]', '-an', '-c:v', 'libx264', '-crf', '18', '-preset', 'medium', '-movflags', '+faststart',
    join(assetDir, 'loops', `${scene}.mp4`)]);
  const posterTime = scene === 'conversation' ? '2.7' : '1.8';
  run('ffmpeg', ['-hide_banner', '-loglevel', 'error', '-y', '-c:v', 'libvpx-vp9', '-i', webm,
    '-ss', posterTime, '-frames:v', '1', '-pix_fmt', 'rgba', join(assetDir, 'loops', `${scene}-poster.png`)]);
}
