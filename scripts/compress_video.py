#!/usr/bin/env python3
import sys
import os
import subprocess
import tempfile
import platform

def compress_video(input_path, output_path=None):
    """
    Comprime um vídeo mantendo qualidade, reduzindo tamanho gradualmente até <= 20MB.
    Se output_path for fornecido, salva o resultado lá. Caso contrário, sobrescreve o arquivo original.
    """
    if not os.path.exists(input_path):
        print(f"Erro: Arquivo {input_path} não encontrado")
        return False

    # Se não foi fornecido output_path, usar o mesmo arquivo
    if output_path is None:
        output_path = input_path

    # Obter tamanho original
    original_size = os.path.getsize(input_path)
    print(f"Tamanho original: {original_size / (1024*1024):.2f} MB")

    # Criar arquivo temporário para output se estamos sobrescrevendo
    if output_path == input_path:
        with tempfile.NamedTemporaryFile(suffix='.mp4', delete=False) as temp_out:
            temp_output_path = temp_out.name
    else:
        temp_output_path = output_path

    max_attempts = 10
    attempt = 0
    current_crf = 20
    current_scale = None  # None = resolução original
    current_audio_bitrate = 128

    # Detectar sistema operacional para controle de threads
    is_linux = platform.system() == 'Linux'

    try:
        while attempt < max_attempts:
            attempt += 1

            # Construir comando FFmpeg
            cmd = ['ffmpeg', '-i', input_path, '-c:v', 'libx264', '-crf', str(current_crf)]

            # Adicionar escala se necessário
            if current_scale:
                cmd.extend(['-vf', f'scale=-2:{current_scale}'])

            cmd.extend([
                '-c:a', 'aac', '-b:a', f'{current_audio_bitrate}k',
                '-preset', 'medium',
            ])

            # Limitar threads apenas no Linux para controlar uso de CPU
            if is_linux:
                cmd.extend(['-threads', '1'])

            cmd.extend(['-y', temp_output_path])

            print(f"Tentativa {attempt}: CRF={current_crf}, Escala={current_scale or 'original'}, Audio={current_audio_bitrate}k")

            # Executar compressão
            result = subprocess.run(cmd, capture_output=True, text=True, timeout=300)

            if result.returncode != 0:
                print(f"Erro na tentativa {attempt}: {result.stderr}")
                # Tentar próxima configuração
                current_crf += 3
                if current_crf > 35:
                    current_crf = 35
                    if current_scale is None:
                        current_scale = 720
                    elif current_scale == 720:
                        current_scale = 480
                    elif current_scale == 480:
                        current_scale = 360
                    else:
                        current_audio_bitrate = max(32, current_audio_bitrate - 32)
                continue

            # Verificar tamanho
            compressed_size = os.path.getsize(temp_output_path)
            size_mb = compressed_size / (1024 * 1024)
            print(f"Tamanho após tentativa {attempt}: {size_mb:.2f} MB")

            if size_mb <= 20.0:
                # Sucesso!
                if output_path == input_path:
                    # Sobrescrever arquivo original
                    os.replace(temp_output_path, input_path)
                print(f"Compressão bem-sucedida na tentativa {attempt}")
                return True

            # Se ainda grande, aumentar compressão
            current_crf += 3
            if current_crf > 35:
                current_crf = 35
                if current_scale is None:
                    current_scale = 720
                elif current_scale == 720:
                    current_scale = 480
                elif current_scale == 480:
                    current_scale = 360
                else:
                    current_audio_bitrate = max(32, current_audio_bitrate - 32)

        print("Falha: Não foi possível comprimir o vídeo para menos de 20MB após todas as tentativas")
        return False

    except subprocess.TimeoutExpired:
        print("Erro: Timeout na compressão")
        return False
    except Exception as e:
        print(f"Erro na compressão: {e}")
        return False
    finally:
        # Limpar arquivo temporário se ainda existir e for diferente do output
        if output_path == input_path and os.path.exists(temp_output_path):
            try:
                os.unlink(temp_output_path)
            except:
                pass

if __name__ == "__main__":
    if len(sys.argv) < 2 or len(sys.argv) > 3:
        print("Uso: python compress_video.py <caminho_do_video> [caminho_output]")
        sys.exit(1)

    video_path = sys.argv[1]
    output_path = sys.argv[2] if len(sys.argv) == 3 else None

    success = compress_video(video_path, output_path)
    sys.exit(0 if success else 1)