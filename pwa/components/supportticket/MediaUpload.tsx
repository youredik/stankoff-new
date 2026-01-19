import React, {useCallback, useState} from 'react';
import {Box, Typography, Paper, LinearProgress, IconButton, Alert} from '@mui/material';
import {CloudUpload, Delete, Download, Image, VideoFile} from '@mui/icons-material';
import {useCreate, useDelete, useGetList} from 'react-admin';

interface MediaFile {
  id: number;
  filename: string;
  originalName: string;
  mimeType: string;
  size: number;
  createdAt: string;
  downloadUrl: string;
}

interface MediaUploadProps {
  ticketId: string;
  onMediaChange?: () => void;
}

export const MediaUpload: React.FC<MediaUploadProps> = ({ticketId, onMediaChange}) => {
  const [dragOver, setDragOver] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(0);
  const [error, setError] = useState<string | null>(null);

  const [create] = useCreate();
  const [deleteOne] = useDelete();

  const {data: mediaFiles, isLoading, refetch} = useGetList(
    'support_ticket_media',
    {
      filter: {supportTicket: `/api/support_tickets/${ticketId}`},
      sort: {field: 'createdAt', order: 'ASC'},
    }
  );

  const handleDragOver = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setDragOver(true);
  }, []);

  const handleDragLeave = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setDragOver(false);
  }, []);

  const handleDrop = useCallback(async (e: React.DragEvent) => {
    e.preventDefault();
    setDragOver(false);

    const files = Array.from(e.dataTransfer.files);
    if (files.length === 0) return;

    await uploadFiles(files);
  }, [ticketId]);

  const handleFileSelect = useCallback(async (e: React.ChangeEvent<HTMLInputElement>) => {
    const files = Array.from(e.target.files || []);
    if (files.length === 0) return;

    await uploadFiles(files);
  }, [ticketId]);

  const uploadFiles = async (files: File[]) => {
    setUploading(true);
    setError(null);
    setUploadProgress(0);

    try {
      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const formData = new FormData();
        formData.append('file', file);

        await create(
          `support_tickets/${ticketId}/media`,
          {
            data: formData,
          }
        );

        setUploadProgress(((i + 1) / files.length) * 100);
      }

      refetch();
      onMediaChange?.();
    } catch (err) {
      setError('Ошибка при загрузке файлов');
      console.error('Upload error:', err);
    } finally {
      setUploading(false);
      setUploadProgress(0);
    }
  };

  const handleDelete = async (mediaId: number) => {
    try {
      await deleteOne('support_ticket_media', {id: mediaId});
      refetch();
      onMediaChange?.();
    } catch (err) {
      setError('Ошибка при удалении файла');
      console.error('Delete error:', err);
    }
  };

  const handleDownload = (media: MediaFile) => {
    window.open(media.downloadUrl, '_blank');
  };

  const formatFileSize = (bytes: number) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  const isImage = (mimeType: string) => mimeType.startsWith('image/');
  const isVideo = (mimeType: string) => mimeType.startsWith('video/');

  return (
    <Box>
      <Typography variant="h6" gutterBottom>
        Медиа файлы
      </Typography>

      {/* Upload Area */}
      <Paper
        sx={{
          p: 3,
          mb: 2,
          border: '2px dashed',
          borderColor: dragOver ? 'primary.main' : 'grey.300',
          backgroundColor: dragOver ? 'primary.50' : 'grey.50',
          cursor: 'pointer',
          transition: 'all 0.2s ease',
          position: 'relative',
        }}
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onDrop={handleDrop}
        onClick={() => document.getElementById('file-input')?.click()}
      >
        <input
          id="file-input"
          type="file"
          multiple
          accept="image/*,video/*"
          style={{display: 'none'}}
          onChange={handleFileSelect}
        />

        <Box textAlign="center">
          <CloudUpload sx={{fontSize: 48, color: 'grey.400', mb: 1}} />
          <Typography variant="body1" color="textSecondary">
            Перетащите файлы сюда или нажмите для выбора
          </Typography>
          <Typography variant="body2" color="textSecondary">
            Поддерживаются изображения и видео
          </Typography>
        </Box>

        {uploading && (
          <Box sx={{mt: 2}}>
            <LinearProgress variant="determinate" value={uploadProgress} />
            <Typography variant="body2" color="textSecondary" sx={{mt: 1}}>
              Загрузка... {Math.round(uploadProgress)}%
            </Typography>
          </Box>
        )}
      </Paper>

      {error && (
        <Alert severity="error" sx={{mb: 2}}>
          {error}
        </Alert>
      )}

      {/* Media Files List */}
      {mediaFiles && mediaFiles.length > 0 && (
        <Box>
          <Typography variant="subtitle1" gutterBottom>
            Загруженные файлы ({mediaFiles.length})
          </Typography>

          {mediaFiles.map((media: MediaFile) => (
            <Paper key={media.id} sx={{p: 2, mb: 1, display: 'flex', alignItems: 'center'}}>
              <Box sx={{mr: 2}}>
                {isImage(media.mimeType) && <Image color="primary" />}
                {isVideo(media.mimeType) && <VideoFile color="primary" />}
              </Box>

              <Box sx={{flexGrow: 1}}>
                <Typography variant="body1" noWrap>
                  {media.originalName}
                </Typography>
                <Typography variant="body2" color="textSecondary">
                  {formatFileSize(media.size)} • {new Date(media.createdAt).toLocaleDateString('ru-RU')}
                </Typography>
              </Box>

              <Box>
                <IconButton onClick={() => handleDownload(media)} size="small">
                  <Download />
                </IconButton>
                <IconButton onClick={() => handleDelete(media.id)} size="small" color="error">
                  <Delete />
                </IconButton>
              </Box>
            </Paper>
          ))}
        </Box>
      )}

      {isLoading && (
        <Typography variant="body2" color="textSecondary">
          Загрузка файлов...
        </Typography>
      )}
    </Box>
  );
};
