package com.example.englishvocab.service;

import com.example.englishvocab.entity.Topic;
import com.example.englishvocab.repository.TopicRepository;
import org.springframework.stereotype.Service;

import java.util.List;

@Service
public class TopicService {

    private final TopicRepository repository;

    public TopicService(TopicRepository repository) {
        this.repository = repository;
    }

    public List<Topic> getAll() {
        return repository.findAll();
    }

    public Topic getById(Integer id) {
        return repository.findById(id)
                .orElseThrow(() ->
                        new RuntimeException("Không tìm thấy chủ đề"));
    }

    public Topic create(Topic topic) {
        topic.setId(null);
        return repository.save(topic);
    }

    public Topic update(Integer id, Topic input) {

        Topic topic = getById(id);

        topic.setName(input.getName());
        topic.setDescription(input.getDescription());

        return repository.save(topic);
    }

    public void delete(Integer id) {

        if (!repository.existsById(id)) {
            throw new RuntimeException("Không tìm thấy chủ đề");
        }

        repository.deleteById(id);
    }
}