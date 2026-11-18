import { execFileSync } from "node:child_process";
import { PNG } from "pngjs";
import {
  BarcodeFormat,
  BinaryBitmap,
  DecodeHintType,
  HybridBinarizer,
  MultiFormatReader,
  RGBLuminanceSource,
} from "@zxing/library";

const code = process.argv[2] || "2000000000015";
const svg = execFileSync("php", ["-r", `require 'src/barcode.php'; echo ean13_svg(${JSON.stringify(code)});`], {
  encoding: "utf8",
});
const viewBox = svg.match(/viewBox="0 0 (\d+(?:\.\d+)?) (\d+(?:\.\d+)?)"/);
if (!viewBox) {
  throw new Error("El SVG no tiene viewBox.");
}
const width = Math.ceil(Number(viewBox[1]));
const height = Math.ceil(Number(viewBox[2]));
const scale = 4;
const png = new PNG({ width: width * scale, height: height * scale });
png.data.fill(255);

for (const bar of svg.matchAll(/<rect x="([\d.]+)" y="([\d.]+)" width="([\d.]+)" height="([\d.]+)" fill="#111"/g)) {
  const x0 = Math.round(Number(bar[1]) * scale);
  const y0 = Math.round(Number(bar[2]) * scale);
  const w = Math.max(1, Math.round(Number(bar[3]) * scale));
  const h = Math.max(1, Math.round(Number(bar[4]) * scale));
  for (let y = y0; y < y0 + h && y < png.height; y++) {
    for (let x = x0; x < x0 + w && x < png.width; x++) {
      const index = (png.width * y + x) << 2;
      png.data[index] = 0;
      png.data[index + 1] = 0;
      png.data[index + 2] = 0;
    }
  }
}

const luminances = new Uint8ClampedArray(png.width * png.height);
for (let i = 0; i < luminances.length; i++) {
  luminances[i] = png.data[i * 4];
}
const hints = new Map();
hints.set(DecodeHintType.POSSIBLE_FORMATS, [BarcodeFormat.EAN_13]);
hints.set(DecodeHintType.TRY_HARDER, true);
const reader = new MultiFormatReader();
reader.setHints(hints);
const result = reader.decode(new BinaryBitmap(new HybridBinarizer(new RGBLuminanceSource(luminances, png.width, png.height))));
if (result.getText() !== code) {
  throw new Error(`Se leyó ${result.getText()} en lugar de ${code}`);
}
console.log(`OK  la cámara puede leer ${result.getText()}`);
