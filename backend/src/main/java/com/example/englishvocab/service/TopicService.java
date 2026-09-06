package com.example.englishvocab.service;

import com.example.englishvocab.dto.ApiDtos.TopicDto;
import com.example.englishvocab.dto.ApiDtos.TopicUpsert;
import com.example.englishvocab.entity.Topic;
import com.example.englishvocab.entity.User;
import com.example.englishvocab.repository.TopicRepository;
import com.example.englishvocab.repository.VocabularyRepository;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;

@Service
public class TopicService {
    private final TopicRepository topicRepository;
    private final VocabularyRepository vocabularyRepository;

    public TopicService(TopicRepository topicRepository, VocabularyRepository vocabularyRepository) {
        this.topicRepository = topicRepository;
        this.vocabularyRepository = vocabularyRepository;
    }

    public List<TopicDto> listPublic() {
        return topicRepository.findByIsPublicTrueOrderByTopicNameAsc().stream().map(this::toDto).toList();
    }

    public TopicDto get(Integer id) {
        return toDto(topicRepository.findById(id).orElseThrow(() -> new IllegalArgumentException("Không tìm thấy chủ đề")));
    }

    @Transactional
    public TopicDto create(TopicUpsert req, User admin) {
        if (topicRepository.existsByTopicName(req.topicName())) {
            throw new IllegalStateException("Tên chủ đề đã tồn tại");
        }
        Topic t = new Topic();
        apply(t, req);
        t.setCreatedBy(admin);
        return toDto(topicRepository.save(t));
    }

    @Transactional
    public TopicDto update(Integer id, TopicUpsert req) {
        Topic t = topicRepository.findById(id).orElseThrow(() -> new IllegalArgumentException("Không tìm thấy chủ đề"));
        apply(t, req);
        return toDto(topicRepository.save(t));
    }

    @Transactional
    public void delete(Integer id) {
        if (vocabularyRepository.countByTopic_TopicID(id) > 0) {
            throw new IllegalStateException("Không xóa được chủ đề còn từ vựng (FK RESTRICT)");
        }
        topicRepository.deleteById(id);
    }

    private void apply(Topic t, TopicUpsert req) {
        t.setTopicName(req.topicName());
        t.setTopicDescription(req.description());
        t.setCefrLevel(req.cefrLevel() == null ? "A1" : req.cefrLevel());
        t.setIsPublic(req.isPublic() == null || req.isPublic());
    }

    private TopicDto toDto(Topic t) {
        return new TopicDto(
                t.getTopicID(), t.getTopicName(), t.getTopicDescription(), t.getCefrLevel(),
                t.getIsPublic(), vocabularyRepository.countByTopic_TopicID(t.getTopicID())
        );
    }
}
